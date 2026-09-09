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
        'id',
        'uuid',
        'run_id',
        'run_uuid',
        'section_id_code',
        'code',
        'name',
        'sort_order',
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
