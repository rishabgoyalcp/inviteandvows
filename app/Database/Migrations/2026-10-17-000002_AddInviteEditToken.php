<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInviteEditToken extends Migration
{
    public function up(): void
    {
        // Guests build before they sign up; the token in their session proves they own the draft.
        $this->forge->addColumn('invites', [
            'edit_token' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'format'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX invites_edit_token ON invites (edit_token)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX invites_edit_token ON invites');
        $this->forge->dropColumn('invites', ['edit_token', 'format']);
    }
}
