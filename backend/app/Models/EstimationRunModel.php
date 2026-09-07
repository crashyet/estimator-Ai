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
        'run_uid',
        'project_id',
        'run_timestamp',
        'total_items',
        'mapped_high',
        'mapped_medium',
        'unmapped',
        'high_ratio',
        'engine_stats',
        'created_at',
    ];

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (empty($data['data']['run_uid'])) {
            $bytes = random_bytes(16);
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
            $data['data']['run_uid'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        }
        return $data;
    }

    protected $useTimestamps = false; // We set created_at manually or default
}
