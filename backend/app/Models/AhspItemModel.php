<?php

namespace App\Models;

use CodeIgniter\Model;

class AhspItemModel extends Model
{
    protected $table            = 'ahsp_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'id_pekerjaan',
        'kode_ahsp',
        'nama_pekerjaan',
        'satuan',
        'harga_satuan',
        'sumber',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
