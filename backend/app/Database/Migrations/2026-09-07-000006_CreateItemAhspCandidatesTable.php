<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemAhspCandidatesTable extends Migration
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
            'item_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'item_uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'rank' => [
                'type'       => 'INT',
                'constraint' => 5,
                'comment'    => 'Peringkat 1 s.d. 5',
            ],
            'id_pekerjaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Kode AHSP, contoh: 4.2.6.1',
            ],
            'nama_pekerjaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,5',
                'default'    => '0.00000',
                'comment'    => 'Final combined rerank score',
            ],
            'base_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,5',
                'null'       => true,
                'comment'    => 'Base vector similarity score',
            ],
            'reranker' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Contoh: bge_m3_hybrid',
            ],
        ]);
        $this->forge->addKey('id', true); // Primary Key
        $this->forge->addUniqueKey('uuid'); // Unique UUID
        $this->forge->addForeignKey('item_id', 'estimation_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['item_id', 'rank'], false, false, 'idx_candidates_item_rank');
        $this->forge->createTable('item_ahsp_candidates', true);
    }

    public function down()
    {
        $this->forge->dropTable('item_ahsp_candidates', true);
    }
}
