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
        if (! service('throttler')->check('login-' . md5($this->request->getIPAddress() . strtolower($email)), 8, 5 * MINUTE)) {
            return redirect()->to('/login')->with('error', lang('Site.tooMany'));
        }
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

    public function forgot()
    {
        return view('auth/forgot', ['sent' => session()->getFlashdata('sent'), 'error' => session()->getFlashdata('error')]);
    }

    /** Always answers the same way, so nobody can find out which emails have accounts. */
    public function sendReset()
    {
        $back = redirect()->to('/forgot')->with('sent', true);
        if (! service('throttler')->check('reset-' . md5($this->request->getIPAddress()), 5, HOUR)) {
            return redirect()->to('/forgot')->with('error', lang('Site.tooMany'));
        }
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $db    = db_connect();
        $user  = filter_var($email, FILTER_VALIDATE_EMAIL) ? $db->table('users')->where('email', $email)->where('is_active', 1)->where('password_hash IS NOT NULL', null, false)->get()->getRowArray() : null;
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $now   = date('Y-m-d H:i:s');
            $db->table('password_resets')->insert(['user_id' => $user['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'created_at' => $now]);
            try {
                $mail = service('email');
                $mail->setTo($user['email']);
                $mail->setSubject(lang('Site.resetSubject'));
                $mail->setMessage(lang('Site.resetBody', [site_url('reset/' . $token)]));
                if (! $mail->send(false)) {
                    log_message('error', 'Reset email failed: ' . $mail->printDebugger(['headers']));
                }
            } catch (\Throwable $e) {
                log_message('error', 'Reset email error: ' . $e->getMessage());
            }
        }

        return $back;
    }

    private function resetRow(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return db_connect()->table('password_resets')->where('token_hash', hash('sha256', $token))->where('used_at', null)->where('expires_at >', date('Y-m-d H:i:s'))->get()->getRowArray();
    }

    public function reset(string $token)
    {
        return view('auth/reset', ['token' => $token, 'valid' => (bool) $this->resetRow($token), 'error' => session()->getFlashdata('error')]);
    }

    public function doReset(string $token)
    {
        $row  = $this->resetRow($token);
        $pass = (string) $this->request->getPost('password');
        if (! $row) {
            return redirect()->to('/reset/' . $token);
        }
        if (strlen($pass) < 8) {
            return redirect()->to('/reset/' . $token)->with('error', lang('Site.badPassword'));
        }
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->table('users')->where('id', $row['user_id'])->update(['password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'updated_at' => $now]);
        $db->table('password_resets')->where('user_id', $row['user_id'])->where('used_at', null)->update(['used_at' => $now]); // every outstanding link dies together

        return redirect()->to('/login')->with('error', lang('Site.passwordChanged'));
    }
}
