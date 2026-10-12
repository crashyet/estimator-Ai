<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class ProjectController extends ResourceController
{
    protected $modelName = 'App\Models\ProjectModel';
    protected $format    = 'json';

    // Format casting data proyek agar konsisten (ID integer, fee & ppn float)
    protected function formatProject(array $project): array
    {
        $project['id']             = (int) $project['id'];
        $project['contractor_fee'] = (float) ($project['contractor_fee'] ?? 10.00);
        $project['ppn']            = (float) ($project['ppn'] ?? 11.00);
        return $project;
    }

    // GET /api/projects: Daftar semua proyek beserta ringkasan estimasi terakhir
    public function index()
    {
        $projectModel = new ProjectModel();
        $projects     = $projectModel->orderBy('created_at', 'DESC')->findAll();
        $db           = Database::connect();

        $data = array_map(function ($project) use ($db) {
            // Ambil estimasi terakhir berdasarkan project_id
            $latestRun = $db->table('estimation_runs')
                ->where('project_id', $project['id'])
                ->orderBy('run_timestamp', 'DESC')
                ->get()
                ->getRowArray();

            $totalBudget = 0;
            if ($latestRun) {
                // Hitung total anggaran dari item estimasi
                $totalBudgetRow = $db->table('estimation_items')
                    ->join('wbs_sections', 'wbs_sections.id = estimation_items.section_id')
                    ->where('wbs_sections.run_id', $latestRun['id'])
                    ->select('COALESCE(SUM(estimation_items.volume * estimation_items.unit_price), 0) AS total_budget', false)
                    ->get()
                    ->getRowArray();

                $totalBudget = (float) ($totalBudgetRow['total_budget'] ?? 0);
            }

            $item = $this->formatProject($project);
            $item['total_budget'] = $totalBudget;
            $item['latest_run']   = $latestRun ? [
                'id'            => (int) $latestRun['id'],
                'uuid'          => $latestRun['uuid'],
                'project_id'    => (int) $latestRun['project_id'],
                'run_timestamp' => $latestRun['run_timestamp'],
                'total_items'   => (int) $latestRun['total_items'],
                'mapped_high'   => (int) $latestRun['mapped_high'],
                'mapped_medium' => (int) $latestRun['mapped_medium'],
                'unmapped'      => (int) $latestRun['unmapped'],
                'high_ratio'    => (float) $latestRun['high_ratio'],
            ] : null;

            return $item;
        }, $projects);

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'data'    => $data
        ]);
    }

    // POST /api/projects: Buat proyek baru (otomatis generate ID & UUID)
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

        // Validasi input data proyek
        if (!$projectModel->validate($data)) {
            return $this->fail($projectModel->errors(), 422);
        }

        $insertId = $projectModel->insert($data);
        if (!$insertId) {
            return $this->fail('Gagal menyimpan proyek.', 500);
        }

        return $this->respondCreated([
            'status'  => 201,
            'success' => true,
            'message' => 'Proyek berhasil dibuat.',
            'data'    => $this->formatProject($projectModel->find($insertId))
        ]);
    }

    // GET /api/projects/(:segment): Detail proyek beserta riwayat estimasi & dokumen (ID atau UUID)
    public function show($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project      = $projectModel->findByIdOrUuid($idOrUuid);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        // Otomatis sinkronkan status jika diakses dari halaman RAB
        $referer = $this->request->getHeaderLine('referer');
        if (!empty($referer) && strpos($referer, '/rab') !== false) {
            $statusLower = strtolower(trim($project['status'] ?? ''));
            if (empty($statusLower) || !in_array($statusLower, ['rab', 'tahap rab', 'penyusunan rab', 'selesai', 'disetujui'])) {
                $projectModel->update($project['id'], ['status' => 'Tahap RAB']);
                $project['status'] = 'Tahap RAB';
            }
        }

        $db   = Database::connect();
        $runs = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orderBy('run_timestamp', 'DESC')
            ->get()
            ->getResultArray();

        $documents = $db->table('project_documents')
            ->where('project_id', $project['id'])
            ->get()
            ->getResultArray();

        $formattedProject = array_merge($this->formatProject($project), [
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

    // PUT/PATCH /api/projects/(:segment): Perbarui metadata proyek (ID atau UUID)
    public function update($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project      = $projectModel->findByIdOrUuid($idOrUuid);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Data JSON tidak valid atau kosong.', 400);
        }

        // Kumpulkan field teks yang dikirimkan
        $data = [];
        foreach (['title', 'client', 'location', 'status', 'summary', 'image'] as $field) {
            if (isset($json[$field])) {
                $data[$field] = trim($json[$field]);
            }
        }
        if (isset($json['contractor_fee'])) $data['contractor_fee'] = (float) $json['contractor_fee'];
        if (isset($json['ppn']))            $data['ppn']            = (float) $json['ppn'];

        if (!empty($data)) {
            $projectModel->update($project['id'], $data);
        }

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Proyek berhasil diperbarui.',
            'data'    => $this->formatProject($projectModel->find($project['id']))
        ]);
    }

    // DELETE /api/projects/(:segment): Hapus proyek (ID atau UUID)
    public function delete($idOrUuid = null)
    {
        $projectModel = new ProjectModel();
        $project      = $projectModel->findByIdOrUuid($idOrUuid);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        // Hapus data proyek beserta riwayatnya
        $projectModel->delete($project['id']);

        return $this->respondDeleted([
            'status'  => 200,
            'success' => true,
            'message' => 'Proyek berhasil dihapus beserta seluruh riwayat estimasinya.'
        ]);
    }
}
