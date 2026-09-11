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
     * List all projects with both id & uuid and summary of latest estimation run
     */
    public function index()
    {
        $projectModel = new ProjectModel();
        $projects = $projectModel->orderBy('created_at', 'DESC')->findAll();

        $db = \Config\Database::connect();

        $data = array_map(function ($project) use ($db) {
            // Get latest estimation run by project_id or project_uuid
            $latestRun = $db->table('estimation_runs')
                ->where('project_id', $project['id'])
                ->orWhere('project_uuid', $project['uuid'])
                ->orderBy('run_timestamp', 'DESC')
                ->get()
                ->getRowArray();

            $totalBudget = 0;
            if ($latestRun) {
                // Calculate total budget from estimation items
                $totalBudgetRow = $db->table('estimation_items')
                    ->join('wbs_sections', 'wbs_sections.id = estimation_items.section_id')
                    ->where('wbs_sections.run_id', $latestRun['id'])
                    ->select('COALESCE(SUM(estimation_items.volume * estimation_items.unit_price), 0) AS total_budget', false)
                    ->get()
                    ->getRowArray();

                $totalBudget = (float) ($totalBudgetRow['total_budget'] ?? 0);
            }

            return [
                'id'             => (int) $project['id'],
                'uuid'           => $project['uuid'],
                'title'          => $project['title'],
                'client'         => $project['client'],
                'location'       => $project['location'] ?? null,
                'contractor_fee' => isset($project['contractor_fee']) ? (float) $project['contractor_fee'] : 10.00,
                'ppn'            => isset($project['ppn']) ? (float) $project['ppn'] : 11.00,
                'status'         => $project['status'],
                'summary'        => $project['summary'],
                'image'          => $project['image'] ?? null,
                'total_budget'   => $totalBudget,
                'latest_run'     => $latestRun ? [
                    'id'            => (int) $latestRun['id'],
                    'uuid'          => $latestRun['uuid'],
                    'project_id'    => (int) $latestRun['project_id'],
                    'project_uuid'  => $latestRun['project_uuid'],
                    'run_timestamp' => $latestRun['run_timestamp'],
                    'total_items'   => (int) $latestRun['total_items'],
                    'mapped_high'   => (int) $latestRun['mapped_high'],
                    'mapped_medium' => (int) $latestRun['mapped_medium'],
                    'unmapped'      => (int) $latestRun['unmapped'],
                    'high_ratio'    => (float) $latestRun['high_ratio'],
                ] : null,
                'created_at'     => $project['created_at'],
                'updated_at'     => $project['updated_at'],
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
     * Create a new project (Generates id & uuid v4)
     */
    public function create()
    {
        $json = $this->request->getJSON(true);

        if (!$json) {
            return $this->fail('Data JSON tidak valid atau kosong.', 400);
        }

        $projectModel = new ProjectModel();

        $data = [
            'title'          => trim($json['title'] ?? ''),
            'client'         => trim($json['client'] ?? ''),
            'location'       => isset($json['location']) ? trim($json['location']) : null,
            'contractor_fee' => isset($json['contractor_fee']) ? (float) $json['contractor_fee'] : 10.00,
            'ppn'            => isset($json['ppn']) ? (float) $json['ppn'] : 11.00,
            'status'         => $json['status'] ?? 'Perencanaan',
            'summary'        => $json['summary'] ?? null,
            'image'          => $json['image'] ?? null,
        ];

        if (!$projectModel->validate($data)) {
            return $this->fail($projectModel->errors(), 422);
        }

        $insertId = $projectModel->insert($data); // returns inserted integer id

        if (!$insertId) {
            return $this->fail('Gagal menyimpan proyek.', 500);
        }

        $newProject = $projectModel->find($insertId);
        $newProject['id']             = (int) $newProject['id'];
        $newProject['contractor_fee'] = (float) ($newProject['contractor_fee'] ?? 10.00);
        $newProject['ppn']            = (float) ($newProject['ppn'] ?? 11.00);

        return $this->respondCreated([
            'status'  => 201,
            'success' => true,
            'message' => 'Proyek berhasil dibuat.',
            'data'    => $newProject
        ]);
    }

    /**
     * GET /api/projects/(:segment)
     * Get single project detail with estimation runs history (Accepts ID or UUID)
     */
    public function show($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($idOrUuid);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $runs = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orWhere('project_uuid', $project['uuid'])
            ->orderBy('run_timestamp', 'DESC')
            ->get()
            ->getResultArray();

        $documents = $db->table('project_documents')
            ->where('project_id', $project['id'])
            ->get()
            ->getResultArray();

        $formattedProject = array_merge($project, [
            'id'              => (int) $project['id'],
            'uuid'            => $project['uuid'],
            'contractor_fee'  => (float) ($project['contractor_fee'] ?? 10.00),
            'ppn'             => (float) ($project['ppn'] ?? 11.00),
            'documents'       => $documents,
            'estimation_runs' => array_map(function ($r) {
                $r['id']         = (int) $r['id'];
                $r['project_id'] = (int) $r['project_id'];
                return $r;
            }, $runs)
        ]);

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'data'    => $formattedProject
        ]);
    }

    /**
     * PUT/PATCH /api/projects/(:segment)
     * Update project metadata (Accepts ID or UUID)
     */
    public function update($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($idOrUuid);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Data JSON tidak valid atau kosong.', 400);
        }

        $data = [];
        if (isset($json['title']))          $data['title']          = trim($json['title']);
        if (isset($json['client']))         $data['client']         = trim($json['client']);
        if (isset($json['location']))       $data['location']       = trim($json['location']);
        if (isset($json['contractor_fee'])) $data['contractor_fee'] = (float) $json['contractor_fee'];
        if (isset($json['ppn']))            $data['ppn']            = (float) $json['ppn'];
        if (isset($json['status']))         $data['status']         = trim($json['status']);
        if (isset($json['summary']))        $data['summary']        = trim($json['summary']);
        if (isset($json['image']))          $data['image']          = trim($json['image']);

        if (!empty($data)) {
            $projectModel->update($project['id'], $data);
        }

        $updatedProject = $projectModel->find($project['id']);
        $updatedProject['id']             = (int) $updatedProject['id'];
        $updatedProject['contractor_fee'] = (float) ($updatedProject['contractor_fee'] ?? 10.00);
        $updatedProject['ppn']            = (float) ($updatedProject['ppn'] ?? 11.00);

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Proyek berhasil diperbarui.',
            'data'    => $updatedProject
        ]);
    }

    /**
     * DELETE /api/projects/(:segment)
     * Delete project (Accepts ID or UUID)
     */
    public function delete($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($idOrUuid);

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
