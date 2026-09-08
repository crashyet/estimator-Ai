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
        'id',
        'uuid',
        'section_id',
        'section_uuid',
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

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = ProjectModel::generateUuidV4();
        }
        return $data;
    }

    public function findByIdOrUuid($idOrUuid)
    {
        if (is_numeric($idOrUuid)) {
            return $this->find($idOrUuid);
        }
        return $this->where('uuid', $idOrUuid)->first();
    }
}
