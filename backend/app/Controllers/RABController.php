<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class RabController extends ResourceController
{
    public function analyze()
    {
        // Jika request membawa berkas unggahan, otomatis delegasikan ke analyzeImage
        if ($this->request->getFile('ded_file') || $this->request->getFile('file')) {
            return $this->analyzeImage();
        }

        $json = $this->request->getJSON(true);

        if (!$json) {
            return $this->respond([
                'success' => false,
                'message' => 'Request body tidak valid.'
            ], 400);
        }

        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/estimate'; 

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->post($pythonUrl, [
                'json' => $json,
                'http_errors' => false,
                'timeout'     => 1200
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());

        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke AI service.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function analyzeImage()
    {
        // 1. Ambil file dari request CodeIgniter (bisa ded_file atau file)
        $file = $this->request->getFile('ded_file') ?? $this->request->getFile('file');

        if (!$file) {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            $maxPost = ini_get('post_max_size') ?: '64M';
            if (empty($_FILES) && $contentLength > 0) {
                $contentLengthMB = round($contentLength / 1048576, 2);
                return $this->respond([
                    'success' => false,
                    'message' => "Ukuran file ({$contentLengthMB} MB) melebihi batas upload web server ($maxPost). Harap gunakan file di bawah $maxPost atau perbesar konfigurasi upload web server."
                ], 413);
            }

            return $this->respond([
                'success' => false,
                'message' => 'File tidak ditemukan di request. Harap sertakan file DED/CAD/BIM/Gambar.'
            ], 400);
        }

        if (!$file->isValid()) {
            return $this->respond([
                'success' => false,
                'message' => 'File tidak valid.',
                'error'   => $file->getErrorString()
            ], 400);
        }

        // VALIDASI FILE BERDASARKAN EKSTENSI & SIZE (MIME di Windows sering kali terbaca text/plain atau octet-stream untuk CAD/BIM)
        $fileExt = strtolower($file->getClientExtension());
        $allowedExtensions = [
            // 1. Format BIM (3D Models & OpenBIM)
            'ifc',             // OpenBIM
            'rvt',                                        // Autodesk Revit
            'nwd', 'nwc',                                 // Autodesk Navisworks
            'skp',                                        // SketchUp

            // 2. Format CAD (2D & 3D Vektor)
            'dwg', 'dxf', 'dwt', 'dwf', 'dwfx',           // AutoCAD & Vektor Standard
            'svg', 'plt', 'hpgl',                  // Grafis Vektor & Plotter

            // 3. Format Dokumen & Gambar DED
            'pdf', 'png', 'jpg', 'jpeg'
        ];

        if (!in_array($fileExt, $allowedExtensions)) {
            return $this->respond([
                'success' => false,
                'message' => 'Format file tidak didukung. Harap unggah berkas DED, CAD, BIM, atau Dokumen yang valid.'
            ], 400);
        }

        // 2. Ambil parameter form
        $projectName = $this->request->getPost('name') ?? 'Proyek BOQ Otomatis';
        $clientName  = $this->request->getPost('client') ?? 'Klien Internal';

        // 3. Arahkan ke URL FastAPI Python (dinamis via .env dengan fallback)
        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/rab/analyze-image';

        $client = \Config\Services::curlrequest();

        // Release PHP session lock so concurrent requests don't block each other
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            // Gunakan Client MIME Type atau octet-stream fallback
            $postMime = $file->getClientMimeType() ?: 'application/octet-stream';

            // Bungkus file untuk dikirim via cURL
            $curlFile = new \CURLFile(
                $file->getTempName(),
                $postMime,
                $file->getClientName()
            );

            // 4. Kirim request multipart ke Python
            $response = $client->post($pythonUrl, [
                'multipart' => [
                    'ded_file' => $curlFile,
                    'name'     => $projectName,
                    'client'   => $clientName
                ],
                'http_errors' => false,
                'timeout'     => 1200 
            ]);

            // 5. Kembalikan response JSON dari Python ke frontend
            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());

        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke AI service (Python API).',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function analyzePrompt()
    {
        // 1. Ambil data JSON atau POST form
        $json = $this->request->getJSON(true);
        $projectName = $json['name'] ?? $this->request->getPost('name') ?? 'Konsep Desain Rumah';
        $clientName  = $json['client'] ?? $this->request->getPost('client') ?? 'Client';
        $promptText  = $json['prompt'] ?? $this->request->getPost('prompt') ?? '';

        if (empty(trim($promptText))) {
            return $this->respond([
                'success' => false,
                'message' => 'Deskripsi konsep rumah / prompt tidak boleh kosong.'
            ], 400);
        }

        // 2. Arahkan ke URL FastAPI Python (dinamis via .env dengan fallback)
        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/rab/analyze-prompt';

        $client = \Config\Services::curlrequest();

        // Release PHP session lock so concurrent requests don't block each other
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            // 3. Kirim request JSON ke Python FastAPI
            $response = $client->post($pythonUrl, [
                'json' => [
                    'name'   => $projectName,
                    'client' => $clientName,
                    'prompt' => $promptText
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ],
                'http_errors' => false,
                'timeout'     => 1200   
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());

        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke AI service (Python API).',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET/POST /api/projects/(:segment)/audit
     * Real-time scan of project WBS items & anomaly detection
     */
    public function auditProject($projectUuidOrId = null)
    {
        $projectModel = new \App\Models\ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);

        if (!$project) {
            return $this->respond([
                'success' => false,
                'message' => 'Proyek tidak ditemukan.'
            ], 404);
        }

        $db = \Config\Database::connect();
        
        $latestRun = $db->table('estimation_runs')
            ->where('project_id', $project['id'])
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $sections = [];
        if ($latestRun) {
            $secRows = $db->table('wbs_sections')
                ->where('run_id', $latestRun['id'])
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($secRows as $sec) {
                $items = $db->table('estimation_items')
                    ->where('section_id', $sec['id'])
                    ->orderBy('item_no', 'ASC')
                    ->get()
                    ->getResultArray();
                $sec['items'] = $items;
                $sections[] = $sec;
            }
        }

        $totalItems = 0;
        $criticalCount = 0;
        $warningCount = 0;
        $anomalies = [];

        foreach ($sections as $sec) {
            foreach ($sec['items'] as $it) {
                $totalItems++;
                $name = !empty($it['ahsp_name']) ? $it['ahsp_name'] : $it['item_name'];
                $vol = (float) $it['volume'];
                $price = (float) $it['unit_price'];
                $status = $it['ahsp_status'] ?? 'unmapped';

                if ($vol <= 0) {
                    $criticalCount++;
                    $anomalies[] = [
                        'item_id' => $it['id'],
                        'item_name' => $name,
                        'type' => 'critical',
                        'subtitle' => 'Anomali Volume 0.00 ' . ($it['unit'] ?: 'm2'),
                        'reason' => 'Item pekerjaan ini bernilai 0.00 ' . ($it['unit'] ?: 'm2') . ' padahal terdapat rincian pekerjaan struktur.'
                    ];
                } else if ($status === 'unmapped' || $price <= 0) {
                    $warningCount++;
                    $anomalies[] = [
                        'item_id' => $it['id'],
                        'item_name' => $name,
                        'type' => 'warning',
                        'subtitle' => 'Kelengkapan Item AHSP',
                        'reason' => 'Item alas/rincian pekerjaan belum dipetakan secara lengkap ke standar AHSP.'
                    ];
                }
            }
        }

        $normalCount = max(0, $totalItems - ($criticalCount + $warningCount));
        $normalPercent = $totalItems > 0 ? round(($normalCount / $totalItems) * 100) : 100;

        return $this->respond([
            'success' => true,
            'summary' => [
                'total' => $totalItems,
                'critical' => $criticalCount,
                'warning' => $warningCount,
                'normal' => $normalCount,
                'normal_percentage' => $normalPercent
            ],
            'anomalies' => $anomalies
        ]);
    }

    /**
     * GET /api/projects/(:segment)/chat-history
     * Retrieves stored chat history from database
     */
    public function getChatHistory($projectUuidOrId = null)
    {
        $projectModel = new \App\Models\ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);

        if (!$project) {
            return $this->respond([
                'success' => false,
                'message' => 'Proyek tidak ditemukan.'
            ], 404);
        }

        $db = \Config\Database::connect();
        $history = $db->table('ai_chat_histories')
            ->where('project_id', $project['id'])
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $formatted = array_map(function ($row) {
            $actions = null;
            if (!empty($row['actions_data'])) {
                $decoded = json_decode($row['actions_data'], true);
                if (is_array($decoded)) {
                    $actions = $decoded;
                }
            }
            return [
                'id'          => (int) $row['id'],
                'project_id'  => (int) $row['project_id'],
                'sender'      => $row['sender'],
                'message'     => $row['message'],
                'actions'     => $actions,
                'cost_impact' => (float) ($row['cost_impact'] ?? 0),
                'is_applied'  => (int) ($row['is_applied'] ?? 0),
                'created_at'  => $row['created_at'],
            ];
        }, $history);

        return $this->respond([
            'success'  => true,
            'messages' => $formatted
        ]);
    }

    /**
     * PATCH /api/projects/(:segment)/chat-history/(:num)/applied
     * Mark an action proposal in chat history as applied or update item-level applied status
     */
    public function markActionApplied($projectUuidOrId = null, $historyId = null)
    {
        $db = \Config\Database::connect();
        $json = $this->request->getJSON(true) ?: [];

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($json['actions']) && is_array($json['actions'])) {
            $updateData['actions_data'] = json_encode($json['actions']);
            $allApplied = true;
            foreach ($json['actions'] as $act) {
                if (empty($act['is_applied'])) {
                    $allApplied = false;
                    break;
                }
            }
            $updateData['is_applied'] = isset($json['is_applied']) ? (int) $json['is_applied'] : ($allApplied ? 1 : 0);
        } else {
            $updateData['is_applied'] = isset($json['is_applied']) ? (int) $json['is_applied'] : 1;
        }

        $db->table('ai_chat_histories')
            ->where('id', (int) $historyId)
            ->update($updateData);

        return $this->respond([
            'success' => true,
            'message' => 'Status usulan perubahan berhasil diperbarui.'
        ]);
    }

    /**
     * DELETE /api/projects/(:segment)/chat-history
     * Clear all consultation chat history for a project
     */
    public function clearChatHistory($projectUuidOrId = null)
    {
        $projectModel = new \App\Models\ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $db->table('ai_chat_histories')->where('project_id', $project['id'])->delete();

        return $this->respond([
            'success' => true,
            'message' => 'Riwayat konsultasi AI berhasil dibersihkan.'
        ]);
    }

    /**
     * POST /api/projects/(:segment)/chat
     * Handles AI Chat consultation queries and persists history in database
     */
    public function chatProject($projectUuidOrId = null)
    {
        $json = $this->request->getJSON(true);
        $prompt = trim($json['prompt'] ?? $this->request->getPost('prompt') ?? '');

        if (empty($prompt)) {
            return $this->respond([
                'success' => false,
                'message' => 'Pertanyaan tidak boleh kosong.'
            ], 400);
        }

        $projectModel = new \App\Models\ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectUuidOrId);

        if (!$project) {
            return $this->respond([
                'success' => false,
                'message' => 'Proyek tidak ditemukan.'
            ], 404);
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // 1. Save User Prompt to Database
        $db->table('ai_chat_histories')->insert([
            'project_id' => $project['id'],
            'sender'     => 'user',
            'message'    => $prompt,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // 2. Try proxying to Python FastAPI AI RAB Agent (Port 8200) for real LLM response
        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/v2/ai/rab-agent';

        $client = \Config\Services::curlrequest();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $projectName = $project['title'] ?? $project['name'] ?? 'Proyek RAB';
            $response = $client->post($pythonUrl, [
                'json' => [
                    'project_id'       => (string) $project['id'],
                    'prompt'           => $prompt,
                    'project_context'  => [
                        'project_name'     => $projectName,
                        'building_type'    => $project['building_type'] ?? 'Rumah Tinggal',
                        'location'         => [
                            'province'     => $project['province'] ?? 'Jawa Tengah',
                            'city_regency' => $project['location'] ?? $project['city'] ?? 'Banyumas'
                        ],
                        'building_area_m2' => (float)($project['building_area'] ?? 100),
                        'number_of_floors' => (int)($project['floors'] ?? 1),
                        'currency'         => 'IDR'
                    ],
                    'items'            => $json['items'] ?? []
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ],
                'http_errors' => false,
                'timeout'     => 60,
                'connect_timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $bodyData = json_decode($response->getBody(), true);
                $reply = $bodyData['reply_message'] ?? ($bodyData['reply'] ?? '');
                if (!empty($reply)) {
                    $db->table('ai_chat_histories')->insert([
                        'project_id'   => $project['id'],
                        'sender'       => 'ai',
                        'message'      => $reply,
                        'actions_data' => !empty($bodyData['actions']) ? json_encode($bodyData['actions']) : null,
                        'cost_impact'  => (float) ($bodyData['cost_impact'] ?? 0),
                        'is_applied'   => 0,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s')
                    ]);
                    $insertedAiId = (int) $db->insertID();

                    return $this->respond([
                        'success'     => true,
                        'reply'       => $reply,
                        'actions'     => $bodyData['actions'] ?? [],
                        'cost_impact' => $bodyData['cost_impact'] ?? 0,
                        'history_id'  => $insertedAiId
                    ]);
                }
            }

            $decoded = json_decode($response->getBody(), true);
            $errMsg = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Agent merespons kode ' . $response->getStatusCode());
            if (is_array($errMsg)) $errMsg = json_encode($errMsg);

            return $this->respond([
                'success' => false,
                'message' => 'Layanan AI Agent gagal: ' . $errMsg
            ], 502);

        } catch (\Exception $e) {
            log_message('error', 'Python AI Agent error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke AI Agent (' . $pythonUrl . '): ' . $e->getMessage()
            ], 502);
        }
    }

    public function auditAI($projectUuidOrId = null)
    {
        try {
            $json = $this->request->getJSON(true) ?: [];
        } catch (\Throwable $e) {
            $json = [];
        }

        if (empty($json) && $this->request->getPost()) {
            $json = $this->request->getPost();
        }

        if ($projectUuidOrId && empty($json['project_id'])) {
            $projectModel = new \App\Models\ProjectModel();
            $project = $projectModel->findByIdOrUuid($projectUuidOrId);
            if ($project) {
                $json['project_id'] = (string) $project['id'];
                if (empty($json['project_context'])) {
                    $json['project_context'] = [
                        'project_name'     => $project['title'] ?? 'Proyek RAB',
                        'building_type'    => $project['building_type'] ?? 'Rumah Tinggal',
                        'location'         => [
                            'province'     => $project['province'] ?? 'Jawa Tengah',
                            'city_regency' => $project['city'] ?? 'Banyumas',
                        ],
                        'building_area_m2' => (float) ($project['building_area'] ?? 100.0),
                        'number_of_floors' => (int) ($project['floors'] ?? 1),
                        'currency'         => 'IDR'
                    ];
                }
            }
        }

        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/v2/ai/rab-audit';

        $client = \Config\Services::curlrequest();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $response = $client->post($pythonUrl, [
                'json'            => $json,
                'headers'         => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ],
                'http_errors' => false,
                'timeout'     => 120
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode === 200) {
                return $this->response
                    ->setStatusCode(200)
                    ->setContentType('application/json')
                    ->setBody($body);
            }

            $decoded = json_decode($body, true);
            $errMsg = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Audit mengembalikan status ' . $statusCode);
            if (is_array($errMsg)) {
                $errMsg = json_encode($errMsg);
            }

            return $this->respond([
                'status'  => 'error',
                'message' => 'Layanan AI RAB Audit gagal: ' . $errMsg
            ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 502);

        } catch (\Exception $e) {
            log_message('error', 'Python AI Audit API (Port 8200) offline: ' . $e->getMessage());
            return $this->respond([
                'status'  => 'error',
                'message' => 'Tidak dapat terhubung ke AI RAB Audit (' . $pythonUrl . '): ' . $e->getMessage()
            ], 502);
        }
    }

    public function agentAI($projectUuidOrId = null)
    {
        try {
            $json = $this->request->getJSON(true) ?: [];
        } catch (\Throwable $e) {
            $json = [];
        }

        if (empty($json) && $this->request->getPost()) {
            $json = $this->request->getPost();
        }

        $prompt = $json['prompt'] ?? '';

        if (empty($prompt)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Instruksi / prompt untuk AI Co-Pilot tidak boleh kosong.'
            ], 400);
        }

        $projectId = $json['project_id'] ?? $projectUuidOrId;
        $projectModel = new \App\Models\ProjectModel();
        $project = $projectModel->findByIdOrUuid($projectId);

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Persist User Message to ai_chat_histories and ensure context
        if ($project) {
            $json['project_id'] = (string) $project['id'];
            if (empty($json['project_context'])) {
                $json['project_context'] = [
                    'project_name'     => $project['title'] ?? 'Proyek RAB',
                    'building_type'    => $project['building_type'] ?? 'Rumah Tinggal',
                    'location'         => [
                        'province'     => $project['province'] ?? 'Jawa Tengah',
                        'city_regency' => $project['city'] ?? 'Banyumas',
                    ],
                    'building_area_m2' => (float) ($project['building_area'] ?? 100.0),
                    'number_of_floors' => (int) ($project['floors'] ?? 1),
                    'currency'         => 'IDR'
                ];
            }

            $db->table('ai_chat_histories')->insert([
                'project_id' => $project['id'],
                'sender'     => 'user',
                'message'    => $prompt,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        $envUrl = env('PYTHON_API_URL') ?: 'http://192.168.1.24:8200';
        $pythonBaseUrl = rtrim($envUrl, '/');
        $pythonUrl = $pythonBaseUrl . '/api/v2/ai/rab-agent';

        $client = \Config\Services::curlrequest();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $response = $client->post($pythonUrl, [
                'json'            => $json,
                'headers'         => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ],
                'http_errors'     => false,
                'timeout'         => 60,
                'connect_timeout' => 10
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode === 200) {
                $pyBody = json_decode($body, true);
                if ($pyBody && $project) {
                    $db->table('ai_chat_histories')->insert([
                        'project_id'   => $project['id'],
                        'sender'       => 'ai',
                        'message'      => $pyBody['reply_message'] ?? ($pyBody['reply'] ?? ''),
                        'actions_data' => !empty($pyBody['actions']) ? json_encode($pyBody['actions']) : null,
                        'cost_impact'  => (float) ($pyBody['cost_impact'] ?? 0),
                        'is_applied'   => 0,
                        'created_at'   => $now,
                        'updated_at'   => $now
                    ]);
                    $insertedAiId = (int) $db->insertID();
                    $pyBody['history_id'] = $insertedAiId;
                    return $this->respond($pyBody);
                }

                return $this->response
                    ->setStatusCode(200)
                    ->setContentType('application/json')
                    ->setBody($body);
            }

            $decoded = json_decode($body, true);
            $errMsg = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Agent mengembalikan status ' . $statusCode);
            if (is_array($errMsg)) {
                $errMsg = json_encode($errMsg);
            }

            return $this->respond([
                'status'  => 'error',
                'message' => 'Layanan AI RAB Agent gagal: ' . $errMsg
            ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 502);

        } catch (\Exception $e) {
            log_message('error', 'Python AI Agent API (Port 8200) offline: ' . $e->getMessage());
            return $this->respond([
                'status'  => 'error',
                'message' => 'Tidak dapat terhubung ke AI RAB Agent (' . $pythonUrl . '): ' . $e->getMessage()
            ], 502);
        }
    }
}
