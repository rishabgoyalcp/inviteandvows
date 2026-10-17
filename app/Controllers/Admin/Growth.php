<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Coupons;

/** Admin screens for coupons and blog posts. */
class Growth extends BaseController
{
    // ---- coupons ----
    public function coupons()
    {
        $db   = db_connect();
        $rows = $db->table('coupons')->select('coupons.*, (SELECT COUNT(*) FROM orders o WHERE o.coupon_code = coupons.code AND o.status = \'paid\') AS used')->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();

        return view('admin/coupons', ['rows' => $rows, 'regions' => $db->table('regions')->get()->getResultArray(), 'ok' => session()->getFlashdata('ok')]);
    }

    public function saveCoupon()
    {
        $db   = db_connect();
        $id   = (int) $this->request->getPost('id');
        $now  = date('Y-m-d H:i:s');
        if ($id) { // existing coupons can only be switched on or off, so past orders stay explainable
            $db->table('coupons')->where('id', $id)->update(['is_active' => $this->request->getPost('is_active') ? 1 : 0, 'updated_at' => $now]);

            return redirect()->to('/admin/coupons');
        }
        $type  = $this->request->getPost('type') === 'fixed' ? 'fixed' : 'percent';
        $value = (float) $this->request->getPost('value');
        $code  = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->request->getPost('code')));
        if ($value <= 0 || ($type === 'percent' && $value > 100)) {
            return redirect()->to('/admin/coupons')->with('ok', 'Enter a valid value.');
        }
        $formats = array_values(array_intersect((array) $this->request->getPost('formats'), ['website', 'video', 'canva_design', 'canva_pdf']));
        $row = [
            'type' => $type, 'value' => $type === 'percent' ? (int) round($value) : (int) round($value * 100), 'currency' => $type === 'fixed' ? (string) $this->request->getPost('currency') : null,
            'formats' => $formats ? implode(',', $formats) : null, 'max_uses' => max(0, (int) $this->request->getPost('max_uses')),
            'expires_at' => $this->request->getPost('expires') ? date('Y-m-d 23:59:59', strtotime((string) $this->request->getPost('expires'))) : null, 'source' => 'admin',
        ];
        if ($code === '') {
            Coupons::create($row + ['prefix' => 'IV']);
        } elseif ($db->table('coupons')->where('code', $code)->countAllResults() === 0) {
            $db->table('coupons')->insert($row + ['code' => $code, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        } else {
            return redirect()->to('/admin/coupons')->with('ok', 'That code already exists.');
        }

        return redirect()->to('/admin/coupons');
    }

    // ---- blog ----
    public function blog()
    {
        return view('admin/blog', ['rows' => db_connect()->table('blog_posts')->orderBy('id', 'DESC')->limit(200)->get()->getResultArray(),
            'langs' => db_connect()->table('languages')->where('is_active', 1)->orderBy('id')->get()->getResultArray()]);
    }

    public function editPost(int $id = 0)
    {
        $db   = db_connect();
        $post = $id ? $db->table('blog_posts')->where('id', $id)->get()->getRowArray() : null;

        return view('admin/blog_edit', ['p' => $post ?: ['id' => 0, 'locale' => 'en', 'slug' => '', 'title' => '', 'excerpt' => '', 'body' => '', 'is_published' => 0],
            'langs' => $db->table('languages')->where('is_active', 1)->orderBy('id')->get()->getResultArray()]);
    }

    public function savePost()
    {
        $db     = db_connect();
        $id     = (int) $this->request->getPost('id');
        $locale = (string) $this->request->getPost('locale');
        $slug   = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $this->request->getPost('slug'))), '-');
        $title  = trim((string) $this->request->getPost('title'));
        if (! $db->table('languages')->where('code', $locale)->countAllResults() || $slug === '' || $title === '') {
            return redirect()->back()->with('ok', 'Language, slug and title are required.');
        }
        $dup = $db->table('blog_posts')->where(['locale' => $locale, 'slug' => $slug]);
        if ($id) {
            $dup->where('id !=', $id);
        }
        if ($dup->countAllResults() > 0) {
            return redirect()->back()->with('ok', 'That slug already exists in this language.');
        }
        $pub  = $this->request->getPost('is_published') ? 1 : 0;
        $now  = date('Y-m-d H:i:s');
        $data = ['locale' => $locale, 'slug' => $slug, 'title' => mb_substr($title, 0, 200), 'excerpt' => mb_substr(trim((string) $this->request->getPost('excerpt')), 0, 300),
            'body' => (string) $this->request->getPost('body'), 'is_published' => $pub, 'updated_at' => $now];
        if ($id) {
            $old = $db->table('blog_posts')->where('id', $id)->get()->getRowArray();
            $db->table('blog_posts')->where('id', $id)->update($data + ['published_at' => $pub ? ($old['published_at'] ?: $now) : null]);
        } else {
            $db->table('blog_posts')->insert($data + ['published_at' => $pub ? $now : null, 'created_at' => $now]);
        }

        return redirect()->to('/admin/blog');
    }
}
