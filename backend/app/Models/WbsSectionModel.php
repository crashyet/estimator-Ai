<?php

namespace App\Models;

use CodeIgniter\Model;

class WbsSectionModel extends Model
{
    protected $table            = 'wbs_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'run_id',
        'section_id_code',
        'code',
        'name',
        'sort_order',
    ];

    protected $useTimestamps = false;
}
