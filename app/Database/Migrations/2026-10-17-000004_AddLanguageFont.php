<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLanguageFont extends Migration
{
    public function up(): void
    {
        // Google Fonts family used for this language's script, so no font name lives in code.
        $this->forge->addColumn('languages', [
            'font_family' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('languages', 'font_family');
    }
}
