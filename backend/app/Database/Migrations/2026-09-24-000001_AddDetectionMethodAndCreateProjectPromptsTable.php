<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetectionMethodAndCreateProjectPromptsTable extends Migration
{
    public function up()
    {
        // 1. Tambah kolom detection_method ke tabel estimation_runs jika belum ada
        if (!$this->db->fieldExists('detection_method', 'estimation_runs')) {
            $this->forge->addColumn('estimation_runs', [
                'detection_method' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'file',
                    'null'       => true,
                ],
            ]);
        }

        // 2. Buat tabel project_prompts jika belum ada
        if (!$this->db->tableExists('project_prompts')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'run_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'prompt_text' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('run_id', 'estimation_runs', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('project_prompts', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('project_prompts')) {
            $this->forge->dropTable('project_prompts', true);
        }

        if ($this->db->fieldExists('detection_method', 'estimation_runs')) {
            $this->forge->dropColumn('estimation_runs', 'detection_method');
        }
    }
}


