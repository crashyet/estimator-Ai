<?php

namespace App\Models;

use CodeIgniter\Model;

class EstimationRunModel extends Model
{
    protected $table            = 'estimation_runs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'uuid',
        'project_id',
        'project_uuid',
        'run_timestamp',
        'total_items',
        'mapped_high',
        'mapped_medium',
        'unmapped',
        'high_ratio',
        'engine_stats',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Callbacks
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
