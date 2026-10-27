<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Notifications\Admin\UserProfileUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Notifications\Telegram\TestNotification;
use App\Models\NationalIdLog;
use App\Support\OfficeLocationTranslator;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
class ProfileController extends Controller
{
    public function index()
    {
        $nationalIdEdits = 0;

        // Check if table exists before querying
        if (Schema::hasTable('national_id_logs')) {
            $nationalIdEdits = auth()->user()->nationalIdLogs()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }

        return view('profile.index', [
            'user' => Auth::user(),
        ], compact('nationalIdEdits'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate and update national_id if present
        if ($request->filled('national_id')) {
            $this->updateNationalId($request);
        }

        // Validate and update office_location if present
        if ($request->filled('office_location')) {
            $this->updateOfficeLocation($request);
        }

        // Validate and update phone numbers if present
        if ($request->filled('whatsapp_number') || $request->filled('telegram_number')) {
            $this->updatePhone($request);
        }

        return back()->with('success', 'تم تحديث بياناتك بنجاح.');
    }

    public function updatePhone(Request $request)
    {
        $userId = Auth::id();

        $request->validate([
            'whatsapp_number' => 'nullable|numeric|unique:users,whatsapp_number,' . $userId,
            'telegram_number' => ['nullable', 'regex:/^-?[0-9]+$/', 'unique:users,telegram_number,' . $userId],
        ], [
            'whatsapp_number.unique' => 'رقم الواتساب هذا مسجل لمستخدم آخر.',
            'telegram_number.regex' => 'Chat ID يجب أن يكون رقماً صحيحاً فقط.',
            'telegram_number.unique' => 'Chat ID هذا مسجل لمستخدم آخر.',
        ]);

        $user = Auth::user();

        if ($request->has('whatsapp_number')) {
            $user->whatsapp_number = $request->whatsapp_number;
        }
        if ($request->has('telegram_number')) {
            $user->telegram_number = $request->telegram_number;
        }

        $user->save();

        return back()->with('success', 'تم تحديث بيانات التواصل بنجاح');
    }

    public function updateOfficeLocation(Request $request)
    {
        $request->validate([
            'office_location' => 'required|string|max:255',
        ], [
            'office_location.required' => 'مكان العمل مطلوب لإكمال العملية.',
        ]);

        $user = Auth::user();
        $userId = Auth::id();

        $validated = $request->validate([
            'whatsapp_number' => 'nullable|required_without:telegram_number|numeric|min:10|unique:users,whatsapp_number,' . $userId,
            'telegram_number' => 'nullable|required_without:whatsapp_number|numeric|min:10|unique:users,telegram_number,' . $userId,
        ], [
            'whatsapp_number.required_without' => 'يجب إدخال رقم الواتساب إذا لم يتوفر تيليجرام.',
            'telegram_number.required_without' => 'يجب إدخال رقم التيليجرام إذا لم يتوفر واتساب.',
        ]);

        $user->whatsapp_number = $validated['whatsapp_number'] ?? null;
        $user->telegram_number = $validated['telegram_number'] ?? null;
        $user->save();

        return back()->with('success', 'تم تحديث بيانات التواصل بنجاح.');
    }

    public function updateNationalId(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::id();
       $editCount = $user->nationalIdLogs()
    ->where('created_at', '>=', now()->subDays(30))
    ->count();

        if ($editCount >= 2) {
            return back()->with('info', 'لقد استنفدت مرات التعديل المسموحة. يرجى مراجعة قسم IT.');
        }

        $validated = $request->validate([
            'national_id' => 'required|numeric|digits_between:9,20|unique:users,national_id,' . $userId,
        ], [
            'national_id.required' => 'رقم الهوية مطلوب.',
            'national_id.numeric' => 'رقم الهوية يجب أن يحتوي على أرقام فقط.',
            'national_id.digits_between' => 'رقم الهوية يجب أن يكون بين 9 إلى 20 خانة.',
            'national_id.unique' => 'رقم الهوية هذا مسجل مسبقاً.',
        ]);

        if ($user->national_id != $validated['national_id']) {
            $oldId = $user->national_id;
        $user->national_id = $validated['national_id'];

            $this->fetchAndApplyEmployeeData($user, $validated['national_id']);

            if ($user->auth_type === 'ldap') {
                $this->updateUserFromLdap($user);
            }

          if ($user->save()) {
            $user->nationalIdLogs()->create([
                'old_id' => $oldId,
                'new_id' => $validated['national_id'],
            ]);
        }
            return back()->with('success', 'تم تحديث رقم الهوية بنجاح.');
        }

        return back()->with('info', 'لم يتم تغيير رقم الهوية.');
    }




    private function updateUserFromLdap(User $user)
    {
        if (!extension_loaded('ldap')) {
            Log::warning("LDAP extension not loaded.");
            return;
        }

        $ldap_host = env('LDAP_HOST');
        $ldap_port = env('LDAP_PORT', 389);
        $ldap_base_dn = env('LDAP_BASE_DN');
        $ldap_user = env('LDAP_USERNAME');
        $ldap_pass = env('LDAP_PASSWORD');

        if (!$ldap_host || !$ldap_base_dn || !$ldap_user || !$ldap_pass) {
            Log::error("LDAP configuration incomplete.");
            return;
        }

        $ldap_conn = ldap_connect($ldap_host, $ldap_port);
        if ($ldap_conn) {
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            if (@ldap_bind($ldap_conn, $ldap_user, $ldap_pass)) {
                $username = $user->username;
                $filter = "(sAMAccountName=$username)";
                $attributes = ['cn', 'displayname', 'mail'];
                $result = ldap_search($ldap_conn, $ldap_base_dn, $filter, $attributes);

                if ($result) {
                    $entries = ldap_get_entries($ldap_conn, $result);
                    if ($entries['count'] > 0) {
                        $entry = $entries[0];
                        $dn = $entry['dn'] ?? '';

                        // منع التحديث التلقائي للمستخدمين في OU معين (مثل قسم IT)
                        // إذا كان المستخدم ينتمي لهذا القسم، نتجاوز عملية التحديث من LDAP
                        if (str_contains(strtoupper($dn), 'OU=IT,OU=NMC')) {
                            \Illuminate\Support\Facades\Log::info("Skipping LDAP profile update for protected user: {$user->username}");
                            ldap_close($ldap_conn);
                            return;
                        }

                        // تحديث الاسم والبريد
                        $user->fullname = $entry['cn'][0] ?? $entry['displayname'][0] ?? $user->fullname;
                        $user->email = $entry['mail'][0] ?? $user->email;

                        // استخراج الـ OU وفرضها كمكان عمل (هنا تكمن الأولوية)
                        if (preg_match('/OU=([^,]+)/i', $dn, $matches)) {
                            $user->office_location = $matches[1];
                        }
                    }
                }
                ldap_close($ldap_conn);
            }
        }
    }

    private function fetchAndApplyEmployeeData($user, $nationalId)
    {
        try {
            $baseUrl = env('HR_API_BASE_URL', 'http://10.20.10.100/newwebemp/hr/GET_EMP_INFO');
            $clientId = env('HR_API_CLIENT_ID', '20260310');
            $clientSecret = env('HR_API_CLIENT_SECRET', '20260310');

            // جلب التوكن
            $tokenRes = Http::asForm()->post("{$baseUrl}/get_token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ]);

            $tokenData = json_decode(trim($tokenRes->body(), "\xef\xbb\xbf "), true);
            $token = $tokenData['result']['token'] ?? null;

            if ($token) {
                // جلب البيانات مع الهيدرز الصحيحة (بدون Bearer كما في Postman)
                $response = Http::withHeaders([
                    'Authorization' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post("{$baseUrl}/get_emp_det?client_id={$clientId}&client_secret={$clientSecret}", [
                    'id' => $nationalId
                ]);

                $apiData = json_decode(trim($response->body(), "\xef\xbb\xbf "), true);

                if (isset($apiData['status']) && $apiData['status'] === 'success' && !empty($apiData['data'])) {
                    $employeeData = $apiData['data'][0];

                    $user->fullname = $employeeData['FULL_NAME'] ?? $user->fullname;
                    $user->email = $employeeData['TB_EMPBASICINFO_EMAIL'] ?? $user->email;
                    $user->classification_name = $employeeData['C_JOB_CLASSIFICATION_DET_NAME'] ?? $user->classification_name;

                    // مكان العمل يعتمد فقط على Active Directory (LDAP) ولا يتم أخذه من API.

                    $user->save();
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error("HR API Error: " . $e->getMessage());
        }
        return false;
    }
    public function getUserDetails(User $user) {
        if (!auth()->user()->role->name === 'admin') {
            abort(403);
        }
        return response()->json([
        'full_name' => $user->fullname,
        'email'     => $user->email,
        'whatsapp_number'     => $user->whatsapp_number,
        'telegram_number'     => $user->telegram_number,
        'office_location' => OfficeLocationTranslator::toArabic($user->office_location),
        'classification_name' => $user->classification_name,
        'national_id'         => $user->national_id,
    ]);
    }

    public function testTelegramNotification()
    {
        $user = Auth::user();

        if (!$user->telegram_number) {
            return back()->with('error', 'يرجى ربط الحساب أو إدخال رقم المعرف (Chat ID) أولاً.');
        }

        try {
            $user->notify(new TestNotification());
            return back()->with('success', 'تم إرسال رسالة تجريبية بنجاح. يرجى التحقق من تطبيق تيليجرام.');
        } catch (CouldNotSendNotification $e) {
            Log::warning('Test Telegram notification failed', [
                'notifiable'   => get_class($user) . ':' . $user->id,
                'notification' => TestNotification::class,
                'message'      => $e->getMessage(),
            ]);
            return back()->with('error', 'فشل إرسال الإشعار التجريبي. تأكد من صحة رقم التيليجرام.');
        } catch (\Exception $e) {
            Log::error('Test Telegram notification error', [
                'notifiable'   => get_class($user) . ':' . $user->id,
                'notification' => TestNotification::class,
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'حدث خطأ في إرسال الإشعار: ' . $e->getMessage());
        }
    }

    public function generateTelegramLink(Request $request)
    {
        $user = Auth::user();
        $user->telegram_link_token = Str::random(32);
        $user->save();

        return back()->with('success', 'تم إنشاء رابط الربط بنجاح. يرجى استخدامه لربط حسابك.');
    }
}
