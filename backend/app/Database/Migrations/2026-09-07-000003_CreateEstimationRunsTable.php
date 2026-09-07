<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEstimationRunsTable extends Migration
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
            'run_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'project_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'run_timestamp' => [
                'type' => 'DATETIME',
            ],
            'total_items' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'mapped_high' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'mapped_medium' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'unmapped' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'high_ratio' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'engine_stats' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('estimation_runs', true);
    }

    public function down()
    {
        $this->forge->dropTable('estimation_runs', true);
    }
}
