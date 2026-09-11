<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetailsToProjectsTable extends Migration
{
    public function up()
    {
        $fields = [
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'client',
            ],
            'contractor_fee' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 10.00,
                'after'      => 'location',
            ],
            'ppn' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 11.00,
                'after'      => 'contractor_fee',
            ],
            'image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'summary',
            ],
        ];

        $this->forge->addColumn('projects', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('projects', ['location', 'contractor_fee', 'ppn', 'image']);
    }
}
