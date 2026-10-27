<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        $user = \App\Models\User::where('username', $credentials['username'])->first();

        if ($user && $user->auth_type === 'normal') {
            if (\Illuminate\Support\Facades\Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return redirect()->intended('dashboard');
            }
        } else {
            // LDAP Auth
            if ($this->ldapLogin($credentials['username'], $credentials['password'], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended('dashboard');
            }
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records or LDAP authentication failed.',
        ]);
    }

    private function ldapLogin($username, $password, $remember = false) {
        // Fallback for local testing if LDAP extension is missing
        if (!extension_loaded('ldap')) {
            // For testing only: if user exists in DB, allow login with any password (since we removed password auth)
            // Or better: Still try standard auth if password is not null?
            // User requested "remove the password", implying auth is ONLY ldap.
            // I'll throw an error.
            throw new \Exception("LDAP extension not loaded.");
        }

        $ldap_host = env('LDAP_HOST', '127.0.0.1');
        $ldap_port = env('LDAP_PORT', 389);
        $ldap_domain = env('LDAP_DOMAIN', 'example.com'); // Used for User Principal Name
        $ldap_base_dn = env('LDAP_BASE_DN', 'dc=example,dc=com');

        $ldap_conn = ldap_connect($ldap_host, $ldap_port);
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        // Try binding with the user's credentials
        // Assuming AD style: username@domain or domain\username
        $ldap_user = $username . '@' . $ldap_domain;

        try {
            $bind = @ldap_bind($ldap_conn, $ldap_user, $password);
        } catch (\Exception $e) {
            $bind = false;
        }

        if ($bind) {
            // Auth Successful
            $user = \App\Models\User::where('username', $username)->first();

            // Search LDAP for user details
            $filter = "(sAMAccountName=$username)"; // AD specific, change to (uid=$username) for OpenLDAP
            $result = ldap_search($ldap_conn, $ldap_base_dn, $filter);
            $entries = ldap_get_entries($ldap_conn, $result);

            // Extract the first OU (most specific) from the DN
            $firstOU = null;
            $allOUs = [];
            $dn = '';
            if ($entries['count'] > 0) {
                $entry = $entries[0];
                $dn = $entry['dn'] ?? '';
                if (preg_match_all('/OU=([^,]+)/i', $dn, $matches)) {
                    $allOUs = $matches[1];
                    $firstOU = $allOUs[0];
                }
            }

            // منع التحديث التلقائي للمستخدمين في OU معين (مثل قسم IT)
            // يتم التحقق من الـ DN الخاص بالمستخدم
            $isProtectedUser = str_contains(strtoupper($dn), 'OU=IT,OU=NMC');
            // dd($allOUs);

            if (!$user) {
                if ($entries['count'] > 0) {
                    $entry = $entries[0];
                    $fullname = $entry['cn'][0] ?? $entry['displayname'][0] ?? $username;
                    $email = $entry['mail'][0] ?? $username . '@' . $ldap_domain;

                    $role = \App\Models\Role::where('name', 'Member')->first();

                    $user = \App\Models\User::create([
                        'username' => $username,
                        'fullname' => $fullname,
                        'email' => $email,
                        'password' => null, // No password stored
                        'role_id' => $role ? $role->id : 1,
                        'auth_type' => 'ldap',
                        'office_location' => $firstOU,
                    ]);
                }
            } else {
                // تحديث بيانات المستخدم الحالي عند كل تسجيل دخول، إلا إذا كان من ضمن المجموعة المحمية
                if (!$isProtectedUser) {
                    $user->office_location = $firstOU;
                    $user->save();
                }
            }

            // مزامنة المستشفيات بناءً على OUs، إلا إذا كان المستخدم محمياً
            if ($user && !$isProtectedUser) {
                $hospitalIds = \App\Models\Hospital::whereIn('name', $allOUs)->pluck('id')->toArray();
                $user->hospitals()->sync($hospitalIds);
                $reversedOUs = array_reverse($allOUs);
                session(['current_hospital_name' => $reversedOUs[1] ?? 'غير محدد']);

            }

            ldap_close($ldap_conn);
            \Illuminate\Support\Facades\Auth::login($user, $remember);
            return true;
        }

        return false;
    }

    /*
    public function showRegisterForm() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $data = $request->validate([
            'username' => 'required|unique:users',
            'fullname' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        $role = \App\Models\Role::where('name', 'Member')->first();

        $user = \App\Models\User::create([
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role_id' => $role ? $role->id : 1,
        ]);

        \Illuminate\Support\Facades\Auth::login($user);

        return redirect('dashboard');
    }
    */

    public function logout(Request $request) {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
