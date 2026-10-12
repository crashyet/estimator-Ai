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
        'id', 'uuid', 'project_id', 'run_timestamp', 'total_items', 'mapped_high',
        'mapped_medium', 'unmapped', 'high_ratio', 'engine_stats', 'detection_method'
    ];

    // Konfigurasi waktu pembuatan run
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Hook callback pembuatan UUID
    protected $beforeInsert = ['generateUuid'];

    // Hook otomatis isi UUID v4 sebelum insert jika belum tersedia
    protected function generateUuid(array $data)
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = ProjectModel::generateUuidV4();
        }
        return $data;
    }

    // Cari run estimasi berdasarkan ID numerik atau UUID
    public function findByIdOrUuid($idOrUuid)
    {
        return is_numeric($idOrUuid) ? $this->find($idOrUuid) : $this->where('uuid', $idOrUuid)->first();
    }
}
