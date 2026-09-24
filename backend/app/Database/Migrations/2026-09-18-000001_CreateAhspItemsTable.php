<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAhspItemsTable extends Migration
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
            'id_pekerjaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Kode unik AHSP, contoh: 1.6.14',
            ],
            'kode_ahsp' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Alias kode AHSP untuk kompatibilitas query',
            ],
            'nama_pekerjaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'comment'    => 'Deskripsi lengkap nama item pekerjaan',
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '',
                'comment'    => 'Satuan pengukuran, contoh: m2, m3, buah',
            ],
            'harga_satuan' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => 0.00,
                'comment'    => 'Harga satuan standar AHS PUPR',
            ],
            'sumber' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'CK',
                'comment'    => 'Sumber referensi AHSP, contoh: CK, PUPR, SNI',
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
        $this->forge->addUniqueKey('id_pekerjaan', 'uk_ahsp_id_pekerjaan');
        $this->forge->addKey('nama_pekerjaan', false, false, 'idx_ahsp_nama_pekerjaan');
        $this->forge->createTable('ahsp_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('ahsp_items', true);
    }
}
