<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectDocumentModel extends Model
{
    protected $table            = 'project_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'project_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
