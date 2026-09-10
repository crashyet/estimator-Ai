<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
    protected $table            = 'projects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'uuid',
        'title',
        'client',
        'location',
        'contractor_fee',
        'ppn',
        'status',
        'summary',
        'image',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Callbacks
    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = self::generateUuidV4();
        }
        return $data;
    }

    public static function generateUuidV4(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Find project by ID or UUID
     */
    public function findByIdOrUuid($idOrUuid)
    {
        if (is_numeric($idOrUuid)) {
            return $this->find($idOrUuid);
        }
        return $this->where('uuid', $idOrUuid)->first();
    }

    // Validation
    protected $validationRules = [
        'title'          => 'required|min_length[3]|max_length[255]',
        'client'         => 'permit_empty|max_length[255]',
        'location'       => 'permit_empty|max_length[255]',
        'contractor_fee' => 'permit_empty|numeric',
        'ppn'            => 'permit_empty|numeric',
        'status'         => 'permit_empty|max_length[100]',
        'image'          => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'Judul proyek wajib diisi.',
            'min_length' => 'Judul proyek minimal 3 karakter.',
        ],
    ];
}
