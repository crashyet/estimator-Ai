<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEstimationItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'section_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'section_uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'item_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Contoh: item-A-1',
            ],
            'item_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'item_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Contoh: A.1',
            ],
            'item_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'volume' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'default'    => '0.0000',
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'confidence' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'high',
            ],
            'warning_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ahsp_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ahsp_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ahsp_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ahsp_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,5',
                'null'       => true,
            ],
            'ahsp_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'unmapped',
                'comment'    => 'mapped_high | mapped_medium | unmapped',
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => '0.00',
            ],
            'pipeline_debug_log' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true); // Primary Key
        $this->forge->addUniqueKey('uuid'); // Unique UUID
        $this->forge->addForeignKey('section_id', 'wbs_sections', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['section_id', 'ahsp_status'], false, false, 'idx_items_section_status');
        $this->forge->createTable('estimation_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('estimation_items', true);
    }
}
