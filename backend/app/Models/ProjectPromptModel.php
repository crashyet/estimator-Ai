<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectPromptModel extends Model
{
    protected $table            = 'project_prompts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'run_id',
        'prompt_text',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Fetch prompt by run ID
     */
    public function getByRunId($runId)
    {
        return $this->where('run_id', $runId)->first();
    }
}
