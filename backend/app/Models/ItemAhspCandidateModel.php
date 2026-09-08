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
        'id',
        'uuid',
        'item_id',
        'item_uuid',
        'rank',
        'id_pekerjaan',
        'nama_pekerjaan',
        'satuan',
        'score',
        'base_score',
        'reranker',
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
