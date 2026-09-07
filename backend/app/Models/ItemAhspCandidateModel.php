<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemAhspCandidateModel extends Model
{
    protected $table            = 'item_ahsp_candidates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'item_id',
        'rank',
        'id_pekerjaan',
        'nama_pekerjaan',
        'satuan',
        'score',
        'base_score',
        'reranker',
    ];

    protected $useTimestamps = false;
}
