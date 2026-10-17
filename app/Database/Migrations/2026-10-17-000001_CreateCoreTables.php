<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateCoreTables extends Migration
{
    private function id(): array
    {
        return ['id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true]];
    }

    private function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function fk(string $name, bool $null = false): array
    {
        return [$name => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => $null]];
    }

    private function str(int $len = 255, bool $null = false): array
    {
        return ['type' => 'VARCHAR', 'constraint' => $len, 'null' => $null];
    }

    private function make(string $table, array $fields, array $unique = [], array $keys = []): void
    {
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        foreach ($unique as $u) {
            $this->forge->addUniqueKey($u);
        }
        foreach ($keys as $k) {
            $this->forge->addKey($k);
        }
        $this->forge->createTable($table, true);
    }

    public function up(): void
    {
        // Markets: one row per domain (inviteandvows.in, inviteandvows.com, ...).
        $this->make('regions', $this->id() + [
            'code'           => $this->str(20),
            'domain'         => $this->str(190),
            'currency'       => $this->str(3),
            'default_locale' => $this->str(10),
            'payment_driver' => $this->str(30),
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps(), ['code', 'domain']);

        $this->make('languages', $this->id() + [
            'code'      => $this->str(10),
            'name'      => $this->str(60),
            'native'    => $this->str(60),
            'is_rtl'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->timestamps(), ['code']);

        // Which languages each region offers (nothing hardcoded in code).
        $this->make('region_languages', $this->id() + $this->fk('region_id') + $this->fk('language_id') + [
            'sort' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ], [['region_id', 'language_id']]);

        $this->make('users', $this->id() + [
            'name'          => $this->str(120),
            'email'         => $this->str(190),
            'password_hash' => $this->str(255, true),
            'role'          => $this->str(20),
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps(), ['email']);

        $this->make('event_types', $this->id() + [
            'slug'      => $this->str(60),
            'names'     => ['type' => 'TEXT', 'null' => true], // JSON {"en":"Wedding","hi":"..."}
            'sort'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps(), ['slug']);

        // Fields an invite of this event type can collect. Defined as data, not code.
        $this->make('field_definitions', $this->id() + $this->fk('event_type_id') + [
            'field_key'   => $this->str(60),
            'field_type'  => $this->str(30), // text, textarea, date, time, image, gallery, map, list
            'labels'      => ['type' => 'TEXT', 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'options'     => ['type' => 'TEXT', 'null' => true],
            'sort'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ] + $this->timestamps(), [['event_type_id', 'field_key']]);

        $this->make('templates', $this->id() + $this->fk('event_type_id') + [
            'slug'          => $this->str(120),
            'names'         => ['type' => 'TEXT', 'null' => true],
            'preview_image' => $this->str(255, true),
            'view_file'     => $this->str(190, true),
            'config'        => ['type' => 'TEXT', 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->timestamps(), ['slug'], ['event_type_id']);

        // One row per sellable format of a template, priced per region.
        $this->make('template_formats', $this->id() + $this->fk('template_id') + $this->fk('region_id') + [
            'format'       => $this->str(30), // website, video, canva_design, canva_pdf
            'price_minor'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'delivery_url' => $this->str(500, true), // e.g. Canva template link
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps(), [['template_id', 'region_id', 'format']]);

        $this->make('invites', $this->id() + $this->fk('user_id', true) + $this->fk('template_id') + $this->fk('region_id') + [
            'short_code' => $this->str(12, true),
            'locale'     => $this->str(10),
            'content'    => ['type' => 'TEXT', 'null' => true], // JSON of field values
            'status'     => $this->str(20), // draft, paid, published, expired
            'password'   => $this->str(255, true),
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps(), ['short_code'], ['user_id']);

        $this->make('invite_assets', $this->id() + $this->fk('invite_id') + [
            'kind' => $this->str(20),
            'path' => $this->str(255),
        ] + $this->timestamps(), [], ['invite_id']);

        $this->make('rsvps', $this->id() + $this->fk('invite_id') + [
            'guest_name' => $this->str(120),
            'contact'    => $this->str(120, true),
            'status'     => $this->str(20),
            'guests'     => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'message'    => ['type' => 'TEXT', 'null' => true],
        ] + $this->timestamps(), [], ['invite_id']);

        $this->make('orders', $this->id() + $this->fk('user_id', true) + $this->fk('invite_id') + $this->fk('template_format_id') + [
            'currency'     => $this->str(3),
            'amount_minor' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'       => $this->str(20), // pending, paid, failed, refunded
            'coupon_code'  => $this->str(40, true),
        ] + $this->timestamps(), [], ['user_id', 'invite_id']);

        $this->make('payments', $this->id() + $this->fk('order_id') + [
            'driver'       => $this->str(30),
            'provider_ref' => $this->str(120, true),
            'status'       => $this->str(20),
            'raw'          => ['type' => 'TEXT', 'null' => true],
        ] + $this->timestamps(), [], ['order_id']);
    }

    public function down(): void
    {
        foreach (['payments', 'orders', 'rsvps', 'invite_assets', 'invites', 'template_formats', 'templates',
                  'field_definitions', 'event_types', 'users', 'region_languages', 'languages', 'regions'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
