<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CouponRefUser extends Migration
{
    public function up(): void
    {
        // The friend whose first order earned this reward, so one friend can never earn it twice.
        $this->forge->addColumn('coupons', ['ref_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('coupons', 'ref_user_id');
    }
}
