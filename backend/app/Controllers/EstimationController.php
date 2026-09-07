<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\EstimationRunModel;
use App\Models\WbsSectionModel;
use App\Models\EstimationItemModel;
use App\Models\ItemAhspCandidateModel;
use CodeIgniter\RESTful\ResourceController;

class EstimationController extends ResourceController
{
    protected $format = 'json';

    /**
     * POST /api/projects/(:segment)/save-estimation
     * Saves complete AI Estimation result (Runs, Sections, Items, AHSP Candidates)
     * Supports either integer Project ID or Project UUID
     */
    public function saveEstimation($projectId = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectId);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Payload JSON tidak valid.', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Prepare & Insert estimation_runs
            $summaryMetrics = $json['summary_metrics'] ?? [];
            $engineStats    = $json['engine_stats'] ?? ($json['metadata']['engine_stats'] ?? null);

            $runData = [
                'project_id'    => $project['id'],
                'run_timestamp' => date('Y-m-d H:i:s'),
                'total_items'   => (int) ($summaryMetrics['total_items'] ?? 0),
                'mapped_high'   => (int) ($summaryMetrics['mapped_high'] ?? 0),
                'mapped_medium' => (int) ($summaryMetrics['mapped_medium'] ?? 0),
                'unmapped'      => (int) ($summaryMetrics['unmapped'] ?? 0),
                'high_ratio'    => isset($summaryMetrics['high_ratio']) ? (float) $summaryMetrics['high_ratio'] : null,
                'engine_stats'  => $engineStats ? json_encode($engineStats) : null,
                'created_at'    => date('Y-m-d H:i:s')
            ];

            $runModel = new EstimationRunModel();
            $runId = $runModel->insert($runData);
            $newRun = $runModel->find($runId);

            // 2. Insert WBS Sections & Items
            $sections = $json['sections'] ?? [];
            $wbsSectionModel     = new WbsSectionModel();
            $estimationItemModel = new EstimationItemModel();
            $candidateModel      = new ItemAhspCandidateModel();

            $sortOrder = 1;
            foreach ($sections as $sec) {
                $sectionData = [
                    'run_id'          => $runId,
                    'section_id_code' => $sec['id'] ?? ('sec-' . ($sec['code'] ?? $sortOrder)),
                    'code'            => $sec['code'] ?? '',
                    'name'            => $sec['name'] ?? '',
                    'sort_order'      => $sortOrder++
                ];
                $sectionId = $wbsSectionModel->insert($sectionData);

                // Insert Items
                $items = $sec['items'] ?? [];
                foreach ($items as $item) {
                    $itemAhsp = $item['ahsp_mapping'] ?? [];

                    $itemData = [
                        'section_id'         => $sectionId,
                        'item_uid'           => $item['id'] ?? null,
                        'item_no'            => (int) ($item['no'] ?? 0),
                        'item_code'          => $item['code'] ?? '',
                        'item_name'          => $item['name'] ?? '',
                        'volume'             => (float) ($item['volume'] ?? 0),
                        'unit'               => $item['unit'] ?? '',
                        'confidence'         => $item['confidence'] ?? 'high',
                        'warning_note'       => $item['warning_note'] ?? null,
                        'ahsp_code'          => $itemAhsp['ahsp_code'] ?? ($item['ahsp_code'] ?? null),
                        'ahsp_name'          => $itemAhsp['ahsp_name'] ?? ($item['ahsp_name'] ?? null),
                        'ahsp_unit'          => $itemAhsp['ahsp_unit'] ?? ($item['ahsp_unit'] ?? null),
                        'ahsp_score'         => isset($itemAhsp['ahsp_score']) ? (float) $itemAhsp['ahsp_score'] : (isset($item['ahsp_score']) ? (float)$item['ahsp_score'] : null),
                        'ahsp_status'        => $itemAhsp['ahsp_status'] ?? ($item['ahsp_status'] ?? 'unmapped'),
                        'unit_price'         => (float) ($item['unit_price'] ?? 0),
                        'pipeline_debug_log' => isset($item['pipeline_debug_log']) ? json_encode($item['pipeline_debug_log']) : null,
                    ];

                    $itemId = $estimationItemModel->insert($itemData);

                    // Insert Candidates if available
                    $candidates = $itemAhsp['candidates'] ?? ($item['candidates'] ?? []);
                    if (!empty($candidates) && is_array($candidates)) {
                        $rank = 1;
                        foreach ($candidates as $cand) {
                            $candData = [
                                'item_id'        => $itemId,
                                'rank'           => (int) ($cand['rank'] ?? $rank++),
                                'id_pekerjaan'   => $cand['id_pekerjaan'] ?? ($cand['code'] ?? ''),
                                'nama_pekerjaan' => $cand['nama_pekerjaan'] ?? ($cand['name'] ?? ''),
                                'satuan'         => $cand['satuan'] ?? ($cand['unit'] ?? ''),
                                'score'          => (float) ($cand['score'] ?? 0),
                                'base_score'     => isset($cand['base_score']) ? (float) $cand['base_score'] : null,
                                'reranker'       => $cand['reranker'] ?? null,
                            ];
                            $candidateModel->insert($candData);
                        }
                    }
                }
            }

            // Update project summary if provided in metadata
            if (!empty($json['metadata']['project_summary'])) {
                $projectModel->update($project['id'], [
                    'summary' => $json['metadata']['project_summary']
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->fail('Gagal menyimpan estimasi ke database.', 500);
            }

            return $this->respondCreated([
                'status'  => 201,
                'success' => true,
                'message' => 'Estimasi dan pemetaan AHSP berhasil disimpan ke database.',
                'data'    => [
                    'project_id'   => (int) $project['id'],
                    'project_uuid' => $project['uuid'] ?? null,
                    'run_id'       => (int) $runId,
                    'run_uid'      => $newRun['run_uid'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail([
                'message' => 'Terjadi kesalahan saat menyimpan data estimasi.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/projects/(:segment)/latest-estimation
     * Get the latest estimation run reconstructed in standard WBS JSON format
     * Supports either integer Project ID or Project UUID
     */
    public function getLatestEstimation($projectId = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectId);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $db = \Config\Database::connect();

        $latestRun = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orderBy('run_timestamp', 'DESC')
            ->get()
            ->getRowArray();

        if (!$latestRun) {
            return $this->respond([
                'status'  => 200,
                'success' => true,
                'data'    => null,
                'message' => 'Belum ada data estimasi untuk proyek ini.'
            ]);
        }

        return $this->formatRunData($latestRun['id']);
    }

    /**
     * GET /api/estimation-runs/(:segment)
     * Get specific estimation run by run_id or run_uid
     */
    public function getEstimationRun($runId = null)
    {
        return $this->formatRunData($runId);
    }

    /**
     * PUT/PATCH /api/estimation-items/(:segment)
     * Update an individual estimation item (by id or item_uid)
     */
    public function updateItem($itemId = null)
    {
        $itemModel = new EstimationItemModel();
        
        $item = is_numeric($itemId) 
            ? ($itemModel->find($itemId) ?: $itemModel->where('item_uid', $itemId)->first())
            : $itemModel->where('item_uid', $itemId)->first();

        if (!$item) {
            return $this->failNotFound('Item estimasi tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Payload JSON tidak valid.', 400);
        }

        $allowedFields = [
            'item_name', 'volume', 'unit', 'confidence', 'warning_note',
            'ahsp_code', 'ahsp_name', 'ahsp_unit', 'ahsp_score', 'ahsp_status',
            'unit_price'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $json)) {
                $updateData[$field] = $json[$field];
            }
        }

        if (!empty($updateData)) {
            $itemModel->update($item['id'], $updateData);
        }

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil diperbarui.',
            'data'    => $itemModel->find($item['id'])
        ]);
    }

    /**
     * DELETE /api/estimation-items/(:segment)
     * Delete an individual estimation item
     */
    public function deleteItem($itemId = null)
    {
        $itemModel = new EstimationItemModel();
        
        $item = is_numeric($itemId) 
            ? ($itemModel->find($itemId) ?: $itemModel->where('item_uid', $itemId)->first())
            : $itemModel->where('item_uid', $itemId)->first();

        if (!$item) {
            return $this->failNotFound('Item estimasi tidak ditemukan.');
        }

        $itemModel->delete($item['id']);

        return $this->respondDeleted([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil dihapus.'
        ]);
    }

    /**
     * Helper to reconstruct nested WBS JSON from Database
     */
    private function formatRunData($runIdOrUid)
    {
        $db = \Config\Database::connect();

        $builder = $db->table('estimation_runs');
        if (is_numeric($runIdOrUid)) {
            $builder->where('id', $runIdOrUid)->orWhere('run_uid', $runIdOrUid);
        } else {
            $builder->where('run_uid', $runIdOrUid);
        }

        $run = $builder->get()->getRowArray();

        if (!$run) {
            return $this->failNotFound('Data estimasi tidak ditemukan.');
        }

        $project = $db->table('projects')
            ->where('id', $run['project_id'])
            ->get()
            ->getRowArray();

        $sections = $db->table('wbs_sections')
            ->where('run_id', $run['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $formattedSections = [];
        foreach ($sections as $sec) {
            $items = $db->table('estimation_items')
                ->where('section_id', $sec['id'])
                ->orderBy('item_no', 'ASC')
                ->get()
                ->getResultArray();

            $formattedItems = [];
            foreach ($items as $item) {
                $candidates = $db->table('item_ahsp_candidates')
                    ->where('item_id', $item['id'])
                    ->orderBy('rank', 'ASC')
                    ->get()
                    ->getResultArray();

                $formattedCandidates = array_map(function ($cand) {
                    return [
                        'rank'           => (int) $cand['rank'],
                        'id_pekerjaan'   => $cand['id_pekerjaan'],
                        'nama_pekerjaan' => $cand['nama_pekerjaan'],
                        'satuan'         => $cand['satuan'],
                        'score'          => (float) $cand['score'],
                        'base_score'     => $cand['base_score'] !== null ? (float)$cand['base_score'] : null,
                        'reranker'       => $cand['reranker'],
                    ];
                }, $candidates);

                $formattedItems[] = [
                    'id'                 => $item['item_uid'] ?: 'item-' . $item['id'],
                    'db_id'              => (int) $item['id'],
                    'no'                 => (int) $item['item_no'],
                    'code'               => $item['item_code'],
                    'name'               => $item['item_name'],
                    'volume'             => (float) $item['volume'],
                    'unit'               => $item['unit'],
                    'confidence'         => $item['confidence'],
                    'warning_note'       => $item['warning_note'],
                    'unit_price'         => (float) $item['unit_price'],
                    'ahsp_code'          => $item['ahsp_code'],
                    'ahsp_name'          => $item['ahsp_name'],
                    'ahsp_unit'          => $item['ahsp_unit'],
                    'ahsp_score'         => $item['ahsp_score'] !== null ? (float)$item['ahsp_score'] : null,
                    'ahsp_status'        => $item['ahsp_status'],
                    'ahsp_mapping'       => [
                        'ahsp_code'   => $item['ahsp_code'],
                        'ahsp_name'   => $item['ahsp_name'],
                        'ahsp_unit'   => $item['ahsp_unit'],
                        'ahsp_score'  => $item['ahsp_score'] !== null ? (float)$item['ahsp_score'] : null,
                        'ahsp_status' => $item['ahsp_status'],
                        'candidates'  => $formattedCandidates,
                    ],
                    'candidates'         => $formattedCandidates,
                    'pipeline_debug_log' => $item['pipeline_debug_log'] ? json_decode($item['pipeline_debug_log'], true) : null,
                ];
            }

            $formattedSections[] = [
                'id'         => $sec['section_id_code'],
                'db_id'      => (int) $sec['id'],
                'code'       => $sec['code'],
                'name'       => $sec['name'],
                'sort_order' => (int) $sec['sort_order'],
                'items'      => $formattedItems,
            ];
        }

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'data'    => [
                'run_id'          => (int) $run['id'],
                'run_uid'         => $run['run_uid'] ?? null,
                'project_id'      => (int) $run['project_id'],
                'project_uuid'    => $project['uuid'] ?? null,
                'project_title'   => $project['title'] ?? '',
                'project_client'  => $project['client'] ?? '',
                'run_timestamp'   => $run['run_timestamp'],
                'summary_metrics' => [
                    'total_items'   => (int) $run['total_items'],
                    'mapped_high'   => (int) $run['mapped_high'],
                    'mapped_medium' => (int) $run['mapped_medium'],
                    'unmapped'      => (int) $run['unmapped'],
                    'high_ratio'    => (float) $run['high_ratio'],
                ],
                'engine_stats'    => $run['engine_stats'] ? json_decode($run['engine_stats'], true) : null,
                'sections'        => $formattedSections,
            ]
        ]);
    }
}
