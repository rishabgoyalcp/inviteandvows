<?php

namespace App\Libraries;

class Accounts
{
    /**
     * Sign in an existing customer or create one, then log them in.
     * Returns an error message key, or null on success. Keeps guest draft tokens across the session reset.
     */
    public static function loginOrCreate(string $email, string $pass, string $name): ?string
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
            return 'Site.badAccount';
        }
        $db   = db_connect();
        $user = $db->table('users')->where('email', $email)->get()->getRowArray();
        if ($user) {
            if (! $user['is_active'] || ! $user['password_hash'] || ! password_verify($pass, $user['password_hash'])) {
                return 'Site.invalidLogin';
            }
        } else {
            $now = date('Y-m-d H:i:s');
            $db->table('users')->insert(['name' => mb_substr($name ?: $email, 0, 120), 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'customer', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $user = $db->table('users')->where('id', $db->insertID())->get()->getRowArray();
            Referrals::onSignup((int) $user['id']);
        }
        $drafts = (array) session()->get('drafts');
        session()->regenerate();
        session()->set(['user_id' => (int) $user['id'], 'user_role' => $user['role'], 'user_name' => $user['name'], 'drafts' => $drafts]);

        return null;
    }
}
