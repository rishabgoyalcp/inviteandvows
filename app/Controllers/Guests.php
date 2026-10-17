<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/** A host's guest list: personalised links per guest and one-tap WhatsApp invites and reminders. */
class Guests extends BaseController
{
    private const MAX_GUESTS = 500;

    private function invite(int $id): array
    {
        $inv = db_connect()->table('invites')->where('id', $id)->where('user_id', session()->get('user_id'))->where('status', 'published')->get()->getRowArray();
        if (! $inv) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    public function index(int $id)
    {
        $inv    = $this->invite($id);
        $guests = db_connect()->table('guests g')
            ->select('g.*, (SELECT r.status FROM rsvps r WHERE r.guest_id = g.id ORDER BY r.id DESC LIMIT 1) AS reply')
            ->where('g.invite_id', $id)->orderBy('g.id')->get()->getResultArray();

        return view('dashboard/guests', ['inv' => $inv, 'guests' => $guests, 'ok' => session()->getFlashdata('ok'), 'cc' => \App\Libraries\Region::current()['code'] === 'in' ? '91' : '']);
    }

    public function import(int $id)
    {
        $inv  = $this->invite($id);
        $db   = db_connect();
        $back = redirect()->to('/dashboard/invites/' . $id . '/guests');
        $text = (string) $this->request->getPost('list');
        $file = $this->request->getFile('csv');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($file->getSize() > 256 * 1024) {
                return $back->with('ok', lang('Site.guestsTooBig'));
            }
            $text .= "\n" . file_get_contents($file->getTempName());
        }
        $cc    = preg_replace('/\D/', '', (string) $this->request->getPost('cc'));
        $have  = $db->table('guests')->where('invite_id', $id)->countAllResults();
        $added = 0;
        $now   = date('Y-m-d H:i:s');
        foreach (preg_split('/\r\n|\r|\n/', substr($text, 0, 300000)) as $line) {
            if ($have + $added >= self::MAX_GUESTS) {
                break;
            }
            $cols = array_map('trim', str_getcsv($line));
            $name = mb_substr($cols[0] ?? '', 0, 120);
            if ($name === '' || in_array(strtolower($name), ['name', 'guest'], true)) { // blank lines and a header row
                continue;
            }
            $phone = $this->phone($cols[1] ?? '', $cc);
            $email = filter_var($cols[2] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
            if ($db->table('guests')->where(['invite_id' => $id, 'name' => $name, 'phone' => $phone])->countAllResults() > 0) {
                continue;
            }
            $db->table('guests')->insert(['invite_id' => $id, 'name' => $name, 'phone' => $phone, 'email' => $email, 'token' => bin2hex(random_bytes(8)), 'created_at' => $now, 'updated_at' => $now]);
            $added++;
        }

        return $back->with('ok', lang('Site.guestsAdded', [$added]));
    }

    /** Digits only, with the country code added when a local number was given. Null when it cannot be a phone number. */
    private function phone(string $raw, string $cc): ?string
    {
        $d = preg_replace('/\D/', '', $raw);
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        } elseif ($cc !== '' && strlen($d) <= 11 && ! str_starts_with($d, $cc)) {
            $d = $cc . ltrim($d, '0');
        }

        return strlen($d) >= 8 && strlen($d) <= 15 ? $d : null;
    }

    public function delete(int $id, int $guestId)
    {
        $this->invite($id);
        db_connect()->table('guests')->where(['id' => $guestId, 'invite_id' => $id])->delete();

        return redirect()->to('/dashboard/invites/' . $id . '/guests');
    }

    /** Records the send, then hands over to WhatsApp with the message ready to send. */
    public function whatsapp(int $id, int $guestId)
    {
        $inv   = $this->invite($id);
        $guest = db_connect()->table('guests')->where(['id' => $guestId, 'invite_id' => $id])->get()->getRowArray();
        if (! $guest || ! $guest['phone']) {
            throw PageNotFoundException::forPageNotFound();
        }
        $remind = $this->request->getGet('type') === 'remind';
        $link   = invite_url($inv['short_code']) . (str_contains(invite_url($inv['short_code']), '?') ? '&' : '?') . 'g=' . $guest['token'];
        $msg    = lang($remind ? 'Site.waReminder' : 'Site.waInvite', [$guest['name'], $link], $inv['locale']);
        db_connect()->table('guests')->where('id', $guestId)->update(['last_reminded_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('https://wa.me/' . $guest['phone'] . '?text=' . rawurlencode($msg));
    }
}
