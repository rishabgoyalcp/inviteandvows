<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowOrdersWithoutInvite extends Migration
{
    public function up(): void
    {
        // Canva purchases have no invite: the buyer receives a template link instead.
        $this->forge->modifyColumn('orders', [
            'invite_id' => ['name' => 'invite_id', 'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addColumn('orders', [
            'access_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('orders', 'access_count');
    }
}
