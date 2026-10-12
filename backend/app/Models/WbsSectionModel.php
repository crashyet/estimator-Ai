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
        'id', 'uuid', 'run_id', 'section_id_code', 'code', 'name', 'sort_order'
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

    // Cari WBS section berdasarkan ID numerik atau UUID
    public function findByIdOrUuid($idOrUuid)
    {
        return is_numeric($idOrUuid) ? $this->find($idOrUuid) : $this->where('uuid', $idOrUuid)->first();
    }
}
