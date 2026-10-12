<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\EstimationRunModel;
use App\Models\WbsSectionModel;
use App\Models\EstimationItemModel;
use App\Models\ItemAhspCandidateModel;
use App\Models\ProjectPromptModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class EstimationController extends ResourceController
{
    protected $format = 'json';

    // Cari item estimasi berdasarkan ID numerik, UUID, atau item_uid
    protected function findItem($itemIdOrUuid)
    {
        $itemModel = new EstimationItemModel();
        return $itemModel->findByIdOrUuid($itemIdOrUuid) ?: $itemModel->where('item_uid', $itemIdOrUuid)->first();
    }

    // POST /api/projects/(:segment)/save-estimation: Simpan hasil estimasi AI lengkap (Runs, Sections, Items, AHSP)
    public function saveEstimation($projectUuidOrId = null)
    {
        $projectModel = new ProjectModel();
        $project      = $projectModel->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->fail('Payload JSON tidak valid.', 400);
        }

        $db = Database::connect();
        $db->transStart();

        try {
            // Normalisasi data sections dari beragam variasi struktur payload AI
            $rawSections = $json['sections'] 
                ?? ($json['wbs_sections'] 
                ?? ($json['raw_llm_response']['wbs_sections'] 
                ?? ($json['raw_llm_response']['sections'] ?? null)));

            $sections = [];
            if (!empty($rawSections) && is_array($rawSections)) {
                foreach ($rawSections as $sIdx => $sec) {
                    $secHeader  = $sec['section'] ?? $sec;
                    $sections[] = [
                        'id'    => $secHeader['id'] ?? ('sec-' . ($secHeader['code'] ?? ($sIdx + 1))),
                        'code'  => $secHeader['code'] ?? chr(65 + $sIdx),
                        'name'  => $secHeader['name'] ?? 'PEKERJAAN',
                        'items' => $sec['items'] ?? [],
                    ];
                }
            } elseif (!empty($json['items']) && is_array($json['items'])) {
                // Struktur flat items (misal format frontend): kelompokkan ke section
                $currentSec = null;
                foreach ($json['items'] as $row) {
                    $isSection = (isset($row['type']) && $row['type'] === 'section')
                        || (!isset($row['volume']) && !empty($row['name']) && !empty($row['code']) && strlen($row['code']) <= 2);

                    if ($isSection) {
                        if ($currentSec !== null) $sections[] = $currentSec;
                        $currentSec = [
                            'id'    => $row['id'] ?? ('sec-' . ($row['code'] ?? (count($sections) + 1))),
                            'code'  => $row['code'] ?? chr(65 + count($sections)),
                            'name'  => $row['name'] ?? 'PEKERJAAN',
                            'items' => [],
                        ];
                    } else {
                        if ($currentSec === null) {
                            $currentSec = ['id' => 'sec-A', 'code' => 'A', 'name' => 'PEKERJAAN UTAMA', 'items' => []];
                        }
                        $currentSec['items'][] = $row;
                    }
                }
                if ($currentSec !== null) $sections[] = $currentSec;
            }

            // Hitung metrik ringkasan pemetaan AHSP
            $totalCount = 0;
            $mHigh = 0; $mMedium = 0; $mUnmapped = 0;
            foreach ($sections as $s) {
                foreach ($s['items'] as $it) {
                    $totalCount++;
                    $status = $it['ahsp_status'] ?? ($it['ahsp_mapping']['ahsp_status'] ?? ($it['final_mapping']['ahsp_status'] ?? 'unmapped'));
                    if ($status === 'mapped_high')        $mHigh++;
                    elseif ($status === 'mapped_medium')  $mMedium++;
                    else                                  $mUnmapped++;
                }
            }

            $metrics = $json['summary_metrics'] 
                ?? ($json['raw_llm_response']['summary_metrics'] 
                ?? ($json['metadata']['summary_metrics'] ?? []));

            $totalItems      = (int) ($metrics['total_items'] ?? $totalCount);
            $mappedHigh      = (int) ($metrics['mapped_high'] ?? $mHigh);
            $mappedMedium    = (int) ($metrics['mapped_medium'] ?? $mMedium);
            $unmapped        = (int) ($metrics['unmapped'] ?? $mUnmapped);
            $highRatio       = isset($metrics['high_ratio']) ? (float) $metrics['high_ratio'] : ($totalItems > 0 ? round($mappedHigh / $totalItems, 4) : 0);
            $engineStats     = $json['engine_stats'] ?? ($json['metadata']['engine_stats'] ?? null);
            $detectionMethod = $json['detection_method'] ?? 'file';

            // Simpan data run estimasi baru
            $runModel = new EstimationRunModel();
            $runId    = $runModel->insert([
                'project_id'       => (int) $project['id'],
                'run_timestamp'    => date('Y-m-d H:i:s'),
                'total_items'      => $totalItems,
                'mapped_high'      => $mappedHigh,
                'mapped_medium'    => $mappedMedium,
                'unmapped'         => $unmapped,
                'high_ratio'       => $highRatio,
                'engine_stats'     => $engineStats ? json_encode($engineStats) : null,
                'detection_method' => $detectionMethod,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $runUuid  = $runModel->find($runId)['uuid'];

            // Simpan riwayat prompt jika metode estimasi berbasis teks prompt
            if ($detectionMethod === 'prompt' && !empty($json['prompt_text'])) {
                (new ProjectPromptModel())->insert([
                    'run_id'      => (int) $runId,
                    'prompt_text' => $json['prompt_text'],
                ]);
            }

            // Simpan hierarki WBS Sections, Items, dan Kandidat AHSP
            $wbsSectionModel     = new WbsSectionModel();
            $estimationItemModel = new EstimationItemModel();
            $candidateModel      = new ItemAhspCandidateModel();
            $sortOrder           = 1;

            foreach ($sections as $sec) {
                $sectionId = $wbsSectionModel->insert([
                    'run_id'          => (int) $runId,
                    'section_id_code' => $sec['id'] ?? ('sec-' . ($sec['code'] ?? $sortOrder)),
                    'code'            => $sec['code'] ?? chr(64 + $sortOrder),
                    'name'            => $sec['name'] ?? '',
                    'sort_order'      => $sortOrder++,
                ]);

                foreach (($sec['items'] ?? []) as $iIdx => $item) {
                    $itemAhsp   = $item['ahsp_mapping'] ?? ($item['final_mapping'] ?? []);
                    $ahspCode   = $item['ahsp_code'] ?? ($itemAhsp['ahsp_code'] ?? null);
                    $ahspName   = $item['ahsp_name'] ?? ($itemAhsp['ahsp_name'] ?? null);
                    $ahspUnit   = $item['ahsp_unit'] ?? ($itemAhsp['ahsp_unit'] ?? ($item['unit'] ?? null));
                    $ahspScore  = isset($item['ahsp_score']) ? (float) $item['ahsp_score'] : (isset($itemAhsp['ahsp_score']) ? (float) $itemAhsp['ahsp_score'] : null);
                    $ahspStatus = $item['ahsp_status'] ?? ($itemAhsp['ahsp_status'] ?? 'unmapped');

                    // Auto generate kode AHSP cadangan jika belum terpetakan
                    $rawCode = trim($ahspCode ?? '');
                    if (empty($rawCode) || $rawCode === '-' || $ahspStatus === 'unmapped') {
                        $ahspCode = generate_ahsp_code(
                            $item['name'] ?? '',
                            $sec['name'] ?? '',
                            $sec['code'] ?? '',
                            $sortOrder - 1,
                            $iIdx,
                            $item['code'] ?? ''
                        );
                    }

                    $itemId = $estimationItemModel->insert([
                        'section_id'         => (int) $sectionId,
                        'item_uid'           => $item['id'] ?? null,
                        'item_no'            => (int) ($item['no'] ?? ($iIdx + 1)),
                        'item_code'          => $item['code'] ?? '',
                        'item_name'          => $item['name'] ?? '',
                        'volume'             => (float) ($item['volume'] ?? 0),
                        'unit'               => $item['unit'] ?? '',
                        'confidence'         => $item['confidence'] ?? 'high',
                        'warning_note'       => $item['warning_note'] ?? null,
                        'ahsp_code'          => $ahspCode,
                        'ahsp_name'          => $ahspName ?: ($item['name'] ?? ''),
                        'ahsp_unit'          => $ahspUnit,
                        'ahsp_score'         => $ahspScore,
                        'ahsp_status'        => $ahspStatus,
                        'unit_price'         => (float) ($item['unit_price'] ?? 0),
                        'pipeline_debug_log' => isset($item['pipeline_debug_log']) ? json_encode($item['pipeline_debug_log']) : null,
                    ]);

                    // Simpan daftar kandidat AHSP jika tersedia
                    $candidates = $item['ahsp_candidates'] ?? ($itemAhsp['candidates'] ?? ($itemAhsp['ahsp_candidates'] ?? ($item['candidates'] ?? [])));
                    if (!empty($candidates) && is_array($candidates)) {
                        $rank = 1;
                        foreach ($candidates as $cand) {
                            $candidateModel->insert([
                                'item_id'        => (int) $itemId,
                                'rank'           => (int) ($cand['rank'] ?? $rank++),
                                'id_pekerjaan'   => $cand['id_pekerjaan'] ?? ($cand['code'] ?? ''),
                                'nama_pekerjaan' => $cand['nama_pekerjaan'] ?? ($cand['name'] ?? ''),
                                'satuan'         => $cand['satuan'] ?? ($cand['unit'] ?? ''),
                                'score'          => (float) ($cand['score'] ?? 0),
                                'base_score'     => isset($cand['base_score']) ? (float) $cand['base_score'] : null,
                                'reranker'       => $cand['reranker'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Reset status proyek ke 'Tahap Estimasi' agar user dapat meninjau hasil
            $projectSummary = $json['project_summary'] 
                ?? ($json['raw_llm_response']['project_summary'] 
                ?? ($json['metadata']['project_summary'] ?? null));

            $updateData = ['status' => 'Tahap Estimasi'];
            if (!empty($projectSummary)) $updateData['summary'] = $projectSummary;
            $projectModel->update($project['id'], $updateData);

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

    // GET /api/projects/(:segment)/latest-estimation: Ambil estimasi terakhir dalam format WBS JSON
    public function getLatestEstimation($projectUuidOrId = null)
    {
        $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $latestRun = Database::connect()->table('estimation_runs')
            ->where('project_id', $project['id'])
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

    // GET /api/estimation-runs/(:segment): Ambil detail run estimasi spesifik berdasarkan ID atau UUID
    public function getEstimationRun($runIdOrUuid = null)
    {
        return $this->formatRunData($runIdOrUuid);
    }

    // POST /api/estimation-items & POST /api/projects/(:segment)/items: Tambah item estimasi baru ke section
    public function createItem($projectUuidOrId = null)
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        if (!$json) {
            return $this->fail('Payload JSON tidak valid.', 400);
        }

        $db        = Database::connect();
        $itemModel = new EstimationItemModel();
        $projectId = $json['project_id'] ?? $projectUuidOrId ?? null;
        $project   = $projectId ? (new ProjectModel())->findByIdOrUuid($projectId) : null;
        $sectionId = $json['section_id'] ?? null;
        $category  = trim($json['target_category'] ?? $json['category'] ?? $json['section_name'] ?? '');

        // 1. Verifikasi section jika dikirim langsung
        $secRow = (!empty($sectionId) && is_numeric($sectionId))
            ? $db->table('wbs_sections')->where('id', (int) $sectionId)->get()->getRowArray()
            : null;

        // 2. Resolusi otomatis section dari proyek jika belum ada
        if (!$secRow && $project) {
            $latestRun = $db->table('estimation_runs')
                ->where('project_id', $project['id'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if (!$latestRun) {
                $runUuid = ProjectModel::generateUuidV4();
                $db->table('estimation_runs')->insert([
                    'uuid'          => $runUuid,
                    'project_id'    => $project['id'],
                    'run_timestamp' => date('Y-m-d H:i:s'),
                    'total_items'   => 0,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $latestRun = ['id' => (int) $db->insertID(), 'uuid' => $runUuid];
            }

            $sections = $db->table('wbs_sections')
                ->where('run_id', $latestRun['id'])
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getResultArray();

            if (!empty($category) && !empty($sections)) {
                foreach ($sections as $s) {
                    if (stripos($s['name'], $category) !== false || stripos($category, $s['name']) !== false) {
                        $secRow = $s;
                        break;
                    }
                }
            }

            if (!$secRow && !empty($sections)) {
                $secRow = $sections[0];
            }

            if (!$secRow) {
                $secName = !empty($category) ? strtoupper($category) : 'PEKERJAAN UMUM';
                $secUuid = ProjectModel::generateUuidV4();
                $db->table('wbs_sections')->insert([
                    'uuid'            => $secUuid,
                    'run_id'          => $latestRun['id'],
                    'section_id_code' => 'sec-A',
                    'code'            => 'A',
                    'name'            => $secName,
                    'sort_order'      => 1
                ]);
                $secRow = $db->table('wbs_sections')->where('id', (int) $db->insertID())->get()->getRowArray();
            }
        }

        // 3. Fallback ke sembarang wbs_section jika masih null
        if (!$secRow) {
            $secRow = $db->table('wbs_sections')->orderBy('id', 'ASC')->get()->getRowArray();
        }

        if (!$secRow) {
            return $this->fail('Bagian pekerjaan (section/kategori) tidak ditemukan untuk proyek ini.', 404);
        }

        $sectionId = (int) $secRow['id'];

        // Tentukan nomor urut item berikutnya di section ini
        $maxNoRow = $db->table('estimation_items')->where('section_id', $sectionId)->selectMax('item_no')->get()->getRowArray();
        $nextNo   = ((int) ($maxNoRow['item_no'] ?? 0)) + 1;

        $itemName  = trim($json['item_name'] ?? $json['name'] ?? 'Pekerjaan Baru');
        $ahspCode  = trim($json['ahsp_code'] ?? '');
        $unitPrice = (float) ($json['unit_price'] ?? 0);
        $volume    = (float) ($json['volume'] ?? 1.0);
        $unit      = trim($json['unit'] ?? 'm2');
        $ahspName  = trim($json['ahsp_name'] ?? $itemName);

        // Resolusi harga satuan & kode AHSP otomatis dari tabel referensi master jika belum ada
        if ($unitPrice <= 0 || empty($ahspCode)) {
            $ahspRow = null;
            if (!empty($ahspCode) && $ahspCode !== '-') {
                $ahspRow = $db->table('ahsp_items')->where('id_pekerjaan', $ahspCode)->get()->getRowArray();
            }
            if (!$ahspRow && !empty($itemName)) {
                $ahspRow = $db->table('ahsp_items')->where('nama_pekerjaan', $itemName)->get()->getRowArray()
                    ?: $db->table('ahsp_items')->like('nama_pekerjaan', $itemName)->get()->getRowArray();
            }
            if ($ahspRow) {
                if ($unitPrice <= 0 && !empty($ahspRow['harga_satuan'])) $unitPrice = (float) $ahspRow['harga_satuan'];
                if (empty($ahspCode)) $ahspCode = $ahspRow['id_pekerjaan'];
                if (empty($ahspName) && !empty($ahspRow['nama_pekerjaan'])) $ahspName = $ahspRow['nama_pekerjaan'];
                if (empty($unit) && !empty($ahspRow['satuan'])) $unit = $ahspRow['satuan'];
            }
        }

        $secCode    = $secRow['code'] ?? '1';
        $itemCode   = $secCode . '.' . $nextNo;
        $ahspStatus = !empty($ahspCode) && $ahspCode !== '-' ? 'mapped_high' : ($json['ahsp_status'] ?? 'unmapped');

        $insertId = $itemModel->insert([
            'section_id'   => $sectionId,
            'item_no'      => $nextNo,
            'item_code'    => $itemCode,
            'item_name'    => $itemName,
            'volume'       => $volume,
            'unit'         => $unit,
            'unit_price'   => $unitPrice,
            'ahsp_code'    => $ahspCode ?: null,
            'ahsp_name'    => $ahspName ?: $itemName,
            'ahsp_unit'    => $unit,
            'ahsp_status'  => $ahspStatus,
            'confidence'   => 'high',
        ]);

        if (!$insertId) {
            return $this->fail('Gagal menambahkan item pekerjaan.', 500);
        }

        // Sinkronkan total_items di tabel estimation_runs
        if (!empty($secRow['run_id'])) {
            $totalCount = $db->table('estimation_items as ei')
                ->join('wbs_sections as ws', 'ws.id = ei.section_id')
                ->where('ws.run_id', $secRow['run_id'])
                ->countAllResults();
            $db->table('estimation_runs')->where('id', $secRow['run_id'])->update(['total_items' => $totalCount]);
        }

        $createdItem = $itemModel->find($insertId);
        $subtotal    = (float)$createdItem['volume'] * (float)$createdItem['unit_price'];

        $responseData = array_merge($createdItem, [
            'name'         => $createdItem['item_name'],
            'section_id'   => (int) $secRow['id'],
            'section_code' => $secRow['code'],
            'section_name' => $secRow['name'],
            'subtotal'     => $subtotal,
            'no'           => (int) $createdItem['item_no'],
            'code'         => $createdItem['item_code'] ?: ($secRow['code'] . '.' . $createdItem['item_no']),
        ]);

        return $this->respondCreated([
            'status'  => 201,
            'success' => true,
            'message' => 'Item estimasi berhasil ditambahkan.',
            'data'    => $responseData,
        ]);
    }

    // PUT/PATCH /api/estimation-items/(:segment): Perbarui item estimasi individual (ID, UUID, atau item_uid)
    public function updateItem($itemIdOrUuid = null)
    {
        $item = $this->findItem($itemIdOrUuid);
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
            // Ambil harga_satuan dari ahsp_items otomatis jika ahsp_code diperbarui dan harga belum diisi
            if (isset($updateData['ahsp_code']) && (!isset($updateData['unit_price']) || (float)$updateData['unit_price'] <= 0)) {
                $ahspRow = Database::connect()->table('ahsp_items')->where('id_pekerjaan', $updateData['ahsp_code'])->get()->getRowArray();
                if ($ahspRow && !empty($ahspRow['harga_satuan'])) {
                    $updateData['unit_price'] = (float) $ahspRow['harga_satuan'];
                }
            }

            (new EstimationItemModel())->update($item['id'], $updateData);
        }

        $updatedItem       = (new EstimationItemModel())->find($item['id']);
        $updatedItem['id'] = (int) $updatedItem['id'];

        return $this->respond([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil diperbarui.',
            'data'    => $updatedItem,
        ]);
    }

    // DELETE /api/estimation-items/(:segment): Hapus item estimasi individual
    public function deleteItem($itemIdOrUuid = null)
    {
        $item = $this->findItem($itemIdOrUuid);
        if (!$item) {
            return $this->failNotFound('Item estimasi tidak ditemukan.');
        }

        (new EstimationItemModel())->delete($item['id']);

        return $this->respondDeleted([
            'status'  => 200,
            'success' => true,
            'message' => 'Item estimasi berhasil dihapus.',
        ]);
    }

    // Helper rekonstruksi struktur bersarang WBS JSON dari database
    private function formatRunData($runIdOrUuid)
    {
        $db = Database::connect();

        $run = is_numeric($runIdOrUuid)
            ? $db->table('estimation_runs')->where('id', $runIdOrUuid)->get()->getRowArray()
            : $db->table('estimation_runs')->where('uuid', $runIdOrUuid)->get()->getRowArray();

        if (!$run) {
            return $this->failNotFound('Data estimasi tidak ditemukan.');
        }

        $project = $db->table('projects')->where('id', $run['project_id'])->get()->getRowArray();
        $sections = $db->table('wbs_sections')->where('run_id', $run['id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();

        $formattedSections = [];
        foreach ($sections as $sec) {
            $items = $db->table('estimation_items')->where('section_id', $sec['id'])->orderBy('item_no', 'ASC')->get()->getResultArray();

            $formattedItems = [];
            foreach ($items as $item) {
                $candidates = $db->table('item_ahsp_candidates')->where('item_id', $item['id'])->orderBy('rank', 'ASC')->get()->getResultArray();

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
                'project_uuid'    => $project['uuid'] ?? '',
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
