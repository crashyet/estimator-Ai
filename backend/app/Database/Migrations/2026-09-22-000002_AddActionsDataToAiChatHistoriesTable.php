<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActionsDataToAiChatHistoriesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('ai_chat_histories');

        $columnsToAdd = [];

        if (!in_array('actions_data', $fields)) {
            $columnsToAdd['actions_data'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'message'
            ];
        }

        if (!in_array('cost_impact', $fields)) {
            $columnsToAdd['cost_impact'] = [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00,
                'after' => 'actions_data'
            ];
        }

        if (!in_array('is_applied', $fields)) {
            $columnsToAdd['is_applied'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'cost_impact'
            ];
        }

        if (!empty($columnsToAdd)) {
            $this->forge->addColumn('ai_chat_histories', $columnsToAdd);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('ai_chat_histories', ['actions_data', 'cost_impact', 'is_applied']);
    }
}
