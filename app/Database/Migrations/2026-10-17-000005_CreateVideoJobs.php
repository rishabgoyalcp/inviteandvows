<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideoJobs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'invite_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20], // queued, rendering, done, failed
            'attempts'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'file_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'error'       => ['type' => 'TEXT', 'null' => true],
            'started_at'  => ['type' => 'DATETIME', 'null' => true],
            'finished_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'id']);
        $this->forge->addKey('invite_id');
        $this->forge->createTable('video_jobs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('video_jobs', true);
    }
}
