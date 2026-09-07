<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\EstimationRunModel;
use CodeIgniter\RESTful\ResourceController;

class ProjectController extends ResourceController
{
    protected $modelName = 'App\Models\ProjectModel';
    protected $format    = 'json';

    /**
     * GET /api/projects
     * List all projects with UUID and summary of latest estimation run
     */
    public function index()
    {
        $projectModel = new ProjectModel();
        $projects = $projectModel->orderBy('created_at', 'DESC')->findAll();

        $db = \Config\Database::connect();

        $data = array_map(function ($project) use ($db) {
            // Get latest estimation run
            $latestRun = $db->table('estimation_runs')
                ->where('project_id', $project['id'])
                ->orderBy('run_timestamp', 'DESC')
                ->get()
                ->getRowArray();

            $totalBudget = 0;
            if ($latestRun) {
                // Calculate total budget from estimation items
                $totalBudgetRow = $db->table('estimation_items')
                    ->join('wbs_sections', 'wbs_sections.id = estimation_items.section_id')
                    ->where('wbs_sections.run_id', $latestRun['id'])
                    ->selectSum('estimation_items.volume * estimation_items.unit_price', 'total_budget')
                    ->get()
                    ->getRowArray();

                $totalBudget = (float) ($totalBudgetRow['total_budget'] ?? 0);
            }

            return [
                'id'           => (int) $project['id'],
                'uuid'         => $project['uuid'] ?? null,
                'title'        => $project['title'],
                'client'       => $project['client'],
                'status'       => $project['status'],
                'summary'      => $project['summary'],
                'total_budget' => $totalBudget,
                'latest_run'   => $latestRun ? [
                    'id'            => (int) $latestRun['id'],
                    'run_uid'       => $latestRun['run_uid'] ?? null,
                    'run_timestamp' => $latestRun['run_timestamp'],
                    'total_items'   => (int) $latestRun['total_items'],
                    'mapped_high'   => (int) $latestRun['mapped_high'],
                    'mapped_medium' => (int) $latestRun['mapped_medium'],
                    'unmapped'      => (int) $latestRun['unmapped'],
                    'high_ratio'    => (float) $latestRun['high_ratio'],
                ] : null,
                'created_at'   => $project['created_at'],
                'updated_at'   => $project['updated_at'],
            ];
        }, $projects);

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'data'    => $data
        ]);
    }

    /**
     * POST /api/projects
     * Create a new project (Generates UUID v4 automatically)
     */
    public function create()
    {
        $json = $this->request->getJSON(true);

        if (!$json) {
            return $this->fail('Data JSON tidak valid atau kosong.', 400);
        }

        $projectModel = new ProjectModel();

        $data = [
            'title'   => trim($json['title'] ?? ''),
            'client'  => trim($json['client'] ?? ''),
            'status'  => $json['status'] ?? 'Perencanaan',
            'summary' => $json['summary'] ?? null,
        ];

        if (!$projectModel->validate($data)) {
            return $this->fail($projectModel->errors(), 422);
        }

        $id = $projectModel->insert($data);

        if (!$id) {
            return $this->fail('Gagal menyimpan proyek.', 500);
        }

        $newProject = $projectModel->find($id);

        return $this->respondCreated([
            'status'  => 201,
            'success' => true,
            'message' => 'Proyek berhasil dibuat.',
            'data'    => $newProject
        ]);
    }

    /**
     * GET /api/projects/(:segment)
     * Get single project detail with estimation runs history (Accepts Integer ID or UUID)
     */
    public function show($id = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($id);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $runs = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orderBy('run_timestamp', 'DESC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'data'    => array_merge($project, [
                'estimation_runs' => $runs
            ])
        ]);
    }

    /**
     * PUT/PATCH /api/projects/(:segment)
     * Update project metadata (Accepts Integer ID or UUID)
     */
    public function update($id = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($id);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Data JSON tidak valid atau kosong.', 400);
        }

        $data = [];
        if (isset($json['title']))   $data['title']   = trim($json['title']);
        if (isset($json['client']))  $data['client']  = trim($json['client']);
        if (isset($json['status']))  $data['status']  = trim($json['status']);
        if (isset($json['summary'])) $data['summary'] = trim($json['summary']);

        if (!empty($data)) {
            $projectModel->update($project['id'], $data);
        }

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Proyek berhasil diperbarui.',
            'data'    => $projectModel->find($project['id'])
        ]);
    }

    /**
     * DELETE /api/projects/(:segment)
     * Delete project (Accepts Integer ID or UUID)
     */
    public function delete($id = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($id);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $projectModel->delete($project['id']);

        return $this->respondDeleted([
            'status'  => 200,
            'success' => true,
            'message' => 'Proyek berhasil dihapus beserta seluruh riwayat estimasinya.'
        ]);
    }
}
