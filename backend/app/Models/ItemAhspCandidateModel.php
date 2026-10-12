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
        'id', 'uuid', 'item_id', 'rank', 'id_pekerjaan', 'nama_pekerjaan', 'satuan', 'score', 'base_score', 'reranker'
    ];

    protected $useTimestamps = false;
    protected $beforeInsert  = ['generateUuid'];

    // Hook otomatis isi UUID v4 sebelum insert jika belum tersedia
    protected function generateUuid(array $data)
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = ProjectModel::generateUuidV4();
        }
        return $data;
    }

    // Cari kandidat AHSP berdasarkan ID numerik atau UUID
    public function findByIdOrUuid($idOrUuid)
    {
        return is_numeric($idOrUuid) ? $this->find($idOrUuid) : $this->where('uuid', $idOrUuid)->first();
    }
}
