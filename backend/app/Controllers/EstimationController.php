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
     * Accepts Project ID or UUID
     */
    public function saveEstimation($projectUuidOrId = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);

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
            // Normalize sections from various payload structures:
            $rawSections = $json['sections'] 
                ?? ($json['wbs_sections'] 
                ?? ($json['raw_llm_response']['wbs_sections'] 
                ?? ($json['raw_llm_response']['sections'] ?? null)));

            $sections = [];

            if (!empty($rawSections) && is_array($rawSections)) {
                foreach ($rawSections as $sIdx => $sec) {
                    $secHeader = $sec['section'] ?? $sec;
                    $secItems  = $sec['items'] ?? [];
                    $sections[] = [
                        'id'    => $secHeader['id'] ?? ('sec-' . ($secHeader['code'] ?? ($sIdx + 1))),
                        'code'  => $secHeader['code'] ?? chr(65 + $sIdx),
                        'name'  => $secHeader['name'] ?? 'PEKERJAAN',
                        'items' => $secItems,
                    ];
                }
            } elseif (!empty($json['items']) && is_array($json['items'])) {
                // Flat items array (e.g. to_frontend_format)
                $currentSec = null;
                foreach ($json['items'] as $row) {
                    $isSection = (isset($row['type']) && $row['type'] === 'section')
                        || (!isset($row['volume']) && !empty($row['name']) && !empty($row['code']) && strlen($row['code']) <= 2);

                    if ($isSection) {
                        if ($currentSec !== null) {
                            $sections[] = $currentSec;
                        }
                        $currentSec = [
                            'id'    => $row['id'] ?? ('sec-' . ($row['code'] ?? (count($sections) + 1))),
                            'code'  => $row['code'] ?? chr(65 + count($sections)),
                            'name'  => $row['name'] ?? 'PEKERJAAN',
                            'items' => [],
                        ];
                    } else {
                        if ($currentSec === null) {
                            $currentSec = [
                                'id'    => 'sec-A',
                                'code'  => 'A',
                                'name'  => 'PEKERJAAN UTAMA',
                                'items' => [],
                            ];
                        }
                        $currentSec['items'][] = $row;
                    }
                }
                if ($currentSec !== null) {
                    $sections[] = $currentSec;
                }
            }

            // Summary metrics calculation & fallback
            $totalCount = 0;
            $mHigh = 0;
            $mMedium = 0;
            $mUnmapped = 0;
            foreach ($sections as $s) {
                foreach ($s['items'] as $it) {
                    $totalCount++;
                    $status = $it['ahsp_status'] ?? ($it['ahsp_mapping']['ahsp_status'] ?? ($it['final_mapping']['ahsp_status'] ?? 'unmapped'));
                    if ($status === 'mapped_high') $mHigh++;
                    elseif ($status === 'mapped_medium') $mMedium++;
                    else $mUnmapped++;
                }
            }

            $summaryMetrics = $json['summary_metrics'] 
                ?? ($json['raw_llm_response']['summary_metrics'] 
                ?? ($json['metadata']['summary_metrics'] ?? []));

            $totalItems   = (int) ($summaryMetrics['total_items'] ?? $totalCount);
            $mappedHigh   = (int) ($summaryMetrics['mapped_high'] ?? $mHigh);
            $mappedMedium = (int) ($summaryMetrics['mapped_medium'] ?? $mMedium);
            $unmapped     = (int) ($summaryMetrics['unmapped'] ?? $mUnmapped);
            $highRatio    = isset($summaryMetrics['high_ratio']) 
                ? (float) $summaryMetrics['high_ratio'] 
                : ($totalItems > 0 ? round($mappedHigh / $totalItems, 4) : 0);

            $engineStats  = $json['engine_stats'] ?? ($json['metadata']['engine_stats'] ?? null);

            $runData = [
                'project_id'    => (int) $project['id'],
                'project_uuid'  => $project['uuid'],
                'run_timestamp' => date('Y-m-d H:i:s'),
                'total_items'   => $totalItems,
                'mapped_high'   => $mappedHigh,
                'mapped_medium' => $mappedMedium,
                'unmapped'      => $unmapped,
                'high_ratio'    => $highRatio,
                'engine_stats'  => $engineStats ? json_encode($engineStats) : null,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $runModel = new EstimationRunModel();
            $runId    = $runModel->insert($runData); // returns inserted integer id
            $run      = $runModel->find($runId);
            $runUuid  = $run['uuid'];

            // 2. Insert WBS Sections & Items
            $wbsSectionModel     = new WbsSectionModel();
            $estimationItemModel = new EstimationItemModel();
            $candidateModel      = new ItemAhspCandidateModel();

            $sortOrder = 1;
            foreach ($sections as $sec) {
                $sectionData = [
                    'run_id'          => (int) $runId,
                    'run_uuid'        => $runUuid,
                    'section_id_code' => $sec['id'] ?? ('sec-' . ($sec['code'] ?? $sortOrder)),
                    'code'            => $sec['code'] ?? chr(64 + $sortOrder),
                    'name'            => $sec['name'] ?? '',
                    'sort_order'      => $sortOrder++,
                ];
                $sectionId   = $wbsSectionModel->insert($sectionData); // returns inserted integer id
                $section     = $wbsSectionModel->find($sectionId);
                $sectionUuid = $section['uuid'];

                // Insert Items
                $items = $sec['items'] ?? [];
                foreach ($items as $item) {
                    $itemAhsp = $item['ahsp_mapping'] ?? ($item['final_mapping'] ?? []);

                    $ahspCode   = $item['ahsp_code'] ?? ($itemAhsp['ahsp_code'] ?? null);
                    $ahspName   = $item['ahsp_name'] ?? ($itemAhsp['ahsp_name'] ?? null);
                    $ahspUnit   = $item['ahsp_unit'] ?? ($itemAhsp['ahsp_unit'] ?? ($item['unit'] ?? null));
                    $ahspScore  = isset($item['ahsp_score']) ? (float) $item['ahsp_score'] : (isset($itemAhsp['ahsp_score']) ? (float) $itemAhsp['ahsp_score'] : null);
                    $ahspStatus = $item['ahsp_status'] ?? ($itemAhsp['ahsp_status'] ?? 'unmapped');

                    $itemData = [
                        'section_id'         => (int) $sectionId,
                        'section_uuid'       => $sectionUuid,
                        'item_uid'           => $item['id'] ?? null,
                        'item_no'            => (int) ($item['no'] ?? 0),
                        'item_code'          => $item['code'] ?? '',
                        'item_name'          => $item['name'] ?? '',
                        'volume'             => (float) ($item['volume'] ?? 0),
                        'unit'               => $item['unit'] ?? '',
                        'confidence'         => $item['confidence'] ?? 'high',
                        'warning_note'       => $item['warning_note'] ?? null,
                        'ahsp_code'          => $ahspCode,
                        'ahsp_name'          => $ahspName,
                        'ahsp_unit'          => $ahspUnit,
                        'ahsp_score'         => $ahspScore,
                        'ahsp_status'        => $ahspStatus,
                        'unit_price'         => (float) ($item['unit_price'] ?? 0),
                        'pipeline_debug_log' => isset($item['pipeline_debug_log']) ? json_encode($item['pipeline_debug_log']) : null,
                    ];

                    $itemId   = $estimationItemModel->insert($itemData); // returns inserted integer id
                    $dbItem   = $estimationItemModel->find($itemId);
                    $itemUuid = $dbItem['uuid'];

                    // Insert Candidates if available
                    $candidates = $item['ahsp_candidates'] 
                        ?? ($itemAhsp['candidates'] 
                        ?? ($itemAhsp['ahsp_candidates'] 
                        ?? ($item['candidates'] ?? [])));

                    if (!empty($candidates) && is_array($candidates)) {
                        $rank = 1;
                        foreach ($candidates as $cand) {
                            $candData = [
                                'item_id'        => (int) $itemId,
                                'item_uuid'      => $itemUuid,
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

            // Update project summary if provided
            $projectSummary = $json['project_summary'] 
                ?? ($json['raw_llm_response']['project_summary'] 
                ?? ($json['metadata']['project_summary'] ?? null));

            if (!empty($projectSummary)) {
                $projectModel->update($project['id'], [
                    'summary' => $projectSummary,
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
                    'project_uuid' => $project['uuid'],
                    'run_id'       => (int) $runId,
                    'run_uuid'     => $runUuid,
                ],
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail([
                'message' => 'Terjadi kesalahan saat menyimpan data estimasi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/projects/(:segment)/latest-estimation
     * Get the latest estimation run reconstructed in standard WBS JSON format
     * Accepts Project ID or UUID
     */
    public function getLatestEstimation($projectUuidOrId = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);

        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $db = \Config\Database::connect();

        $latestRun = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orWhere('project_uuid', $project['uuid'])
            ->orderBy('run_timestamp', 'DESC')
            ->get()
            ->getRowArray();

        if (!$latestRun) {
            return $this->respond([
                'status'  => 200,
                'success' => true,
                'data'    => null,
                'message' => 'Belum ada data estimasi untuk proyek ini.',
            ]);
        }

        return $this->formatRunData($latestRun['id']);
    }

    /**
     * GET /api/estimation-runs/(:segment)
     * Get specific estimation run by ID or UUID
     */
    public function getEstimationRun($runIdOrUuid = null)
    {
        return $this->formatRunData($runIdOrUuid);
    }

    /**
     * PUT/PATCH /api/estimation-items/(:segment)
     * Update an individual estimation item (by ID, UUID, or item_uid)
     */
    public function updateItem($itemIdOrUuid = null)
    {
        $itemModel = new EstimationItemModel();

        $item = $itemModel->findByIdOrUuid($itemIdOrUuid)
            ?: $itemModel->where('item_uid', $itemIdOrUuid)->first();

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
            'unit_price',
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

        $updatedItem = $itemModel->find($item['id']);
        $updatedItem['id'] = (int) $updatedItem['id'];

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil diperbarui.',
            'data'    => $updatedItem,
        ]);
    }

    /**
     * DELETE /api/estimation-items/(:segment)
     * Delete an individual estimation item
     */
    public function deleteItem($itemIdOrUuid = null)
    {
        $itemModel = new EstimationItemModel();

        $item = $itemModel->findByIdOrUuid($itemIdOrUuid)
            ?: $itemModel->where('item_uid', $itemIdOrUuid)->first();

        if (!$item) {
            return $this->failNotFound('Item estimasi tidak ditemukan.');
        }

        $itemModel->delete($item['id']);

        return $this->respondDeleted([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil dihapus.',
        ]);
    }

    /**
     * Helper to reconstruct nested WBS JSON from Database
     */
    private function formatRunData($runIdOrUuid)
    {
        $db = \Config\Database::connect();

        $run = is_numeric($runIdOrUuid)
            ? $db->table('estimation_runs')->where('id', $runIdOrUuid)->get()->getRowArray()
            : $db->table('estimation_runs')->where('uuid', $runIdOrUuid)->get()->getRowArray();

        if (!$run) {
            return $this->failNotFound('Data estimasi tidak ditemukan.');
        }

        $project = $db->table('projects')
            ->where('id', $run['project_id'])
            ->orWhere('uuid', $run['project_uuid'])
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
                        'id'             => (int) $cand['id'],
                        'uuid'           => $cand['uuid'],
                        'rank'           => (int) $cand['rank'],
                        'id_pekerjaan'   => $cand['id_pekerjaan'],
                        'nama_pekerjaan' => $cand['nama_pekerjaan'],
                        'satuan'         => $cand['satuan'],
                        'score'          => (float) $cand['score'],
                        'base_score'     => $cand['base_score'] !== null ? (float) $cand['base_score'] : null,
                        'reranker'       => $cand['reranker'],
                    ];
                }, $candidates);

                $formattedItems[] = [
                    'db_id'              => (int) $item['id'],
                    'id'                 => $item['item_uid'] ?: 'item-' . $item['id'],
                    'uuid'               => $item['uuid'],
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
                    'ahsp_score'         => $item['ahsp_score'] !== null ? (float) $item['ahsp_score'] : null,
                    'ahsp_status'        => $item['ahsp_status'],
                    'ahsp_mapping'       => [
                        'ahsp_code'   => $item['ahsp_code'],
                        'ahsp_name'   => $item['ahsp_name'],
                        'ahsp_unit'   => $item['ahsp_unit'],
                        'ahsp_score'  => $item['ahsp_score'] !== null ? (float) $item['ahsp_score'] : null,
                        'ahsp_status' => $item['ahsp_status'],
                        'candidates'  => $formattedCandidates,
                    ],
                    'candidates'         => $formattedCandidates,
                    'pipeline_debug_log' => $item['pipeline_debug_log'] ? json_decode($item['pipeline_debug_log'], true) : null,
                ];
            }

            $formattedSections[] = [
                'db_id'      => (int) $sec['id'],
                'id'         => $sec['section_id_code'],
                'uuid'       => $sec['uuid'],
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
                'run_uuid'        => $run['uuid'],
                'project_id'      => (int) ($project['id'] ?? $run['project_id']),
                'project_uuid'    => $run['project_uuid'],
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
            ],
        ]);
    }
}
