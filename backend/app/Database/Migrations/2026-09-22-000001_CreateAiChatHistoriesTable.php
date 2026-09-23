<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiChatHistoriesTable extends Migration
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
            'sender' => [
                'type'       => 'ENUM',
                'constraint' => ['user', 'ai'],
                'default'    => 'user',
            ],
            'message' => [
                'type' => 'TEXT',
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
        $this->forge->createTable('ai_chat_histories', true);
    }

    public function down()
    {
        $this->forge->dropTable('ai_chat_histories', true);
    }
}
