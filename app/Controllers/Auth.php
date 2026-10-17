<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        return view('auth/login', ['error' => session()->getFlashdata('error')]);
    }

    public function attempt()
    {
        $email = trim((string) $this->request->getPost('email'));
        $pass  = (string) $this->request->getPost('password');
        $user  = db_connect()->table('users')->where('email', $email)->where('is_active', 1)->get()->getRowArray();

        if (! $user || ! $user['password_hash'] || ! password_verify($pass, $user['password_hash'])) {
            return redirect()->to('/login')->with('error', lang('Site.invalidLogin'));
        }
        session()->regenerate();
        session()->set(['user_id' => (int) $user['id'], 'user_role' => $user['role'], 'user_name' => $user['name']]);

        return redirect()->to($user['role'] === 'admin' ? '/admin' : '/');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/');
    }
}
