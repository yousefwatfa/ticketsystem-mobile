<?php

namespace App\Services\Auth;

use App\Models\Hospital;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Checks a username/password the same way for the web login and the mobile API:
 * local accounts (auth_type "normal") against the stored hash, everyone else
 * against Active Directory (LDAP). Successful LDAP logins create or refresh the
 * user and sync hospitals from the AD organisational units.
 */
class CredentialAuthenticator
{
    /**
     * @return array{user: User, hospital_name: ?string}|null  null when the credentials are wrong
     */
    public function attempt(string $username, string $password): ?array
    {
        $user = User::where('username', $username)->first();

        if ($user && $user->auth_type === 'normal') {
            return $user->password && Hash::check($password, $user->password)
                ? ['user' => $user, 'hospital_name' => null]
                : null;
        }

        return $this->ldapAttempt($username, $password);
    }

    private function ldapAttempt(string $username, string $password): ?array
    {
        if (!extension_loaded('ldap')) {
            throw new \Exception('LDAP extension not loaded.');
        }

        $ldap_host = env('LDAP_HOST', '127.0.0.1');
        $ldap_port = env('LDAP_PORT', 389);
        $ldap_domain = env('LDAP_DOMAIN', 'example.com'); // Used for User Principal Name
        $ldap_base_dn = env('LDAP_BASE_DN', 'dc=example,dc=com');

        $ldap_conn = ldap_connect($ldap_host, $ldap_port);
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        // AD style: username@domain
        $ldap_user = $username . '@' . $ldap_domain;

        // an empty password would be an anonymous bind, which AD accepts
        if ($password === '') {
            return null;
        }

        try {
            $bind = @ldap_bind($ldap_conn, $ldap_user, $password);
        } catch (\Exception $e) {
            $bind = false;
        }

        if (!$bind) {
            return null;
        }

        $user = User::where('username', $username)->first();

        // Search LDAP for user details
        $filter = '(sAMAccountName=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')';
        $result = ldap_search($ldap_conn, $ldap_base_dn, $filter);
        $entries = ldap_get_entries($ldap_conn, $result);

        // Extract the first OU (most specific) from the DN
        $firstOU = null;
        $allOUs = [];
        $dn = '';
        if ($entries['count'] > 0) {
            $dn = $entries[0]['dn'] ?? '';
            if (preg_match_all('/OU=([^,]+)/i', $dn, $matches)) {
                $allOUs = $matches[1];
                $firstOU = $allOUs[0];
            }
        }

        // منع التحديث التلقائي للمستخدمين في OU معين (مثل قسم IT)
        $isProtectedUser = str_contains(strtoupper($dn), 'OU=IT,OU=NMC');

        if (!$user) {
            if ($entries['count'] > 0) {
                $entry = $entries[0];
                $fullname = $entry['cn'][0] ?? $entry['displayname'][0] ?? $username;
                $email = $entry['mail'][0] ?? $username . '@' . $ldap_domain;

                $role = Role::where('name', 'Member')->first();

                $user = User::create([
                    'username' => $username,
                    'fullname' => $fullname,
                    'email' => $email,
                    'password' => null, // No password stored
                    'role_id' => $role ? $role->id : 1,
                    'auth_type' => 'ldap',
                    'office_location' => $firstOU,
                ]);
            }
        } elseif (!$isProtectedUser) {
            // تحديث بيانات المستخدم الحالي عند كل تسجيل دخول، إلا إذا كان من ضمن المجموعة المحمية
            $user->office_location = $firstOU;
            $user->save();
        }

        $hospitalName = null;
        // مزامنة المستشفيات بناءً على OUs، إلا إذا كان المستخدم محمياً
        if ($user && !$isProtectedUser) {
            $hospitalIds = Hospital::whereIn('name', $allOUs)->pluck('id')->toArray();
            $user->hospitals()->sync($hospitalIds);
            $reversedOUs = array_reverse($allOUs);
            $hospitalName = $reversedOUs[1] ?? 'غير محدد';
        }

        ldap_close($ldap_conn);

        return $user ? ['user' => $user, 'hospital_name' => $hospitalName] : null;
    }
}
