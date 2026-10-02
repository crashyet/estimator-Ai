<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveRedundantParentUuidColumns extends Migration
{
    public function up()
    {
        // 1. Drop project_uuid from estimation_runs
        if ($this->db->fieldExists('project_uuid', 'estimation_runs')) {
            $this->forge->dropColumn('estimation_runs', 'project_uuid');
        }

        // 2. Drop run_uuid from wbs_sections
        if ($this->db->fieldExists('run_uuid', 'wbs_sections')) {
            $this->forge->dropColumn('wbs_sections', 'run_uuid');
        }

        // 3. Drop section_uuid from estimation_items
        if ($this->db->fieldExists('section_uuid', 'estimation_items')) {
            $this->forge->dropColumn('estimation_items', 'section_uuid');
        }

        // 4. Drop item_uuid from item_ahsp_candidates
        if ($this->db->fieldExists('item_uuid', 'item_ahsp_candidates')) {
            $this->forge->dropColumn('item_ahsp_candidates', 'item_uuid');
        }
    }

    public function down()
    {
        // Restore columns if rolled back
        if (!$this->db->fieldExists('project_uuid', 'estimation_runs')) {
            $this->forge->addColumn('estimation_runs', [
                'project_uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true]
            ]);
        }

        if (!$this->db->fieldExists('run_uuid', 'wbs_sections')) {
            $this->forge->addColumn('wbs_sections', [
                'run_uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true]
            ]);
        }

        if (!$this->db->fieldExists('section_uuid', 'estimation_items')) {
            $this->forge->addColumn('estimation_items', [
                'section_uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true]
            ]);
        }

        if (!$this->db->fieldExists('item_uuid', 'item_ahsp_candidates')) {
            $this->forge->addColumn('item_ahsp_candidates', [
                'item_uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true]
            ]);
        }
    }
}
