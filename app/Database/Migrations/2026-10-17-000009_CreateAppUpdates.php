<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppUpdates extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 12], // update | restore | backup
            'from_version' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'to_version'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 14], // ok | failed | rolled_back
            'backup_file'  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'log'          => ['type' => 'TEXT', 'null' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('app_updates', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('app_updates', true);
    }
}
