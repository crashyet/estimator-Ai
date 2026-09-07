<?php

namespace App\Models;

use CodeIgniter\Model;

class EstimationItemModel extends Model
{
    protected $table            = 'estimation_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'section_id',
        'item_uid',
        'item_no',
        'item_code',
        'item_name',
        'volume',
        'unit',
        'confidence',
        'warning_note',
        'ahsp_code',
        'ahsp_name',
        'ahsp_unit',
        'ahsp_score',
        'ahsp_status',
        'unit_price',
        'pipeline_debug_log',
    ];

    protected $useTimestamps = false;
}
