<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class GrowthTables extends Migration
{
    public function up(): void
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true];
        $ts = ['created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]];
        $fk = static fn (bool $null = false) => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => $null];

        $this->forge->addField(['id' => $id, 'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'type' => ['type' => 'VARCHAR', 'constraint' => 10], // percent | fixed
            'value' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // percent 1-100, or minor units for fixed
            'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true], // fixed coupons only work in this currency
            'formats' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true], // comma list; null = every format
            'max_uses' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0], // 0 = unlimited
            'user_id' => $fk(true), // set = only this customer may use it
            'source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'admin'], // admin | referral
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1]] + $ts);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('coupons', true);

        $this->forge->addField(['id' => $id, 'invite_id' => $fk(), 'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], 'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 24], 'opened_at' => ['type' => 'DATETIME', 'null' => true],
            'last_reminded_at' => ['type' => 'DATETIME', 'null' => true]] + $ts);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('invite_id');
        $this->forge->createTable('guests', true);

        $this->forge->addField(['id' => $id, 'locale' => ['type' => 'VARCHAR', 'constraint' => 10], 'slug' => ['type' => 'VARCHAR', 'constraint' => 120],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200], 'excerpt' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'body' => ['type' => 'MEDIUMTEXT', 'null' => true], 'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'published_at' => ['type' => 'DATETIME', 'null' => true]] + $ts);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->createTable('blog_posts', true);

        $this->forge->addColumn('users', [
            'referral_code' => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'referred_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX users_referral_code ON users (referral_code)');
        $this->forge->addColumn('orders', ['discount_minor' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]]);
        $this->forge->addColumn('rsvps', ['guest_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
    }

    public function down(): void
    {
        $this->forge->dropTable('blog_posts', true);
        $this->forge->dropTable('guests', true);
        $this->forge->dropTable('coupons', true);
    }
}
