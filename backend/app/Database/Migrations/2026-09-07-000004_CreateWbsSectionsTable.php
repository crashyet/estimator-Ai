<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWbsSectionsTable extends Migration
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
            'run_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'run_uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'section_id_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Contoh: sec-A',
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Contoh: A',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Contoh: PEKERJAAN PERSIAPAN & TANAH',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true); // Primary Key
        $this->forge->addUniqueKey('uuid'); // Unique UUID
        $this->forge->addForeignKey('run_id', 'estimation_runs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wbs_sections', true);
    }

    public function down()
    {
        $this->forge->dropTable('wbs_sections', true);
    }
}
