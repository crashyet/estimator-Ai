<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectDocumentsTable extends Migration
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
            'project_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_size' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
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
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_documents', true);
    }

    public function down()
    {
        $this->forge->dropTable('project_documents', true);
    }
}
