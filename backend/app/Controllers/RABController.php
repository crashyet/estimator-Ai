<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;
use Config\Services;
use CURLFile;

class RabController extends ResourceController
{
    // Ambil daftar base URL service Python: Primer (dari .env / eyi) dan Fallback (crashyet)
    protected function getPythonBaseUrls(string $defaultFallback = 'https://crashyet-burnout-predict.hf.space'): array
    {
        $primary  = rtrim(env('PYTHON_API_URL') ?: 'https://api-esti.eyi.my.id', '/');
        $fallback = rtrim($defaultFallback, '/');

        if ($primary === $fallback) {
            return [$primary];
        }

        return [$primary, $fallback];
    }

    // Ambil base URL service Python dari environment dengan opsi fallback URL
    protected function getPythonBaseUrl(string $fallback = 'https://api-esti.eyi.my.id'): string
    {
        return rtrim(env('PYTHON_API_URL') ?: $fallback, '/');
    }

    // Lepaskan session lock PHP agar request concurrent tidak saling memblokir
    protected function closeSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    // Siapkan konteks proyek terstandarisasi untuk payload AI
    protected function buildProjectContext(array $project): array
    {
        return [
            'project_name'     => $project['title'] ?? $project['name'] ?? 'Proyek RAB',
            'building_type'    => $project['building_type'] ?? 'Rumah Tinggal',
            'location'         => [
                'province'     => $project['province'] ?? 'Jawa Tengah',
                'city_regency' => $project['location'] ?? $project['city'] ?? 'Banyumas',
            ],
            'building_area_m2' => (float) ($project['building_area'] ?? 100.0),
            'number_of_floors' => (int) ($project['floors'] ?? 1),
            'currency'         => 'IDR',
        ];
    }



    // POST /api/rab/analyze: Estimasi umum (JSON) atau delegasi otomatis jika ada file upload
    public function analyze()
    {
        // Jika request membawa berkas unggahan, otomatis delegasikan ke analyzeImage
        if ($this->request->getFile('ded_file') || $this->request->getFile('file')) {
            return $this->analyzeImage();
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->respond(['success' => false, 'message' => 'Request body tidak valid.'], 400);
        }

        $url = $this->getPythonBaseUrl('https://crashyet-burnout-predict.hf.space') . '/api/estimate';

        try {
            $response = Services::curlrequest()->post($url, [
                'json'        => $json,
                'http_errors' => false,
                'timeout'     => 1200,
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

    // POST /api/rab/analyze-image: Analisis gambar/dokumen DED, CAD, atau BIM via multipart cURL
    public function analyzeImage()
    {
        $file = $this->request->getFile('ded_file') ?? $this->request->getFile('file');

        if (!$file) {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            $maxPost       = ini_get('post_max_size') ?: '64M';
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

        // Validasi ekstensi berkas (BIM, CAD, PDF, Gambar)
        $fileExt = strtolower($file->getClientExtension());
        $allowedExtensions = [
            'ifc', 'rvt', 'nwd', 'nwc', 'skp',
            'dwg', 'dxf', 'dwt', 'dwf', 'dwfx', 'svg', 'plt', 'hpgl',
            'pdf', 'png', 'jpg', 'jpeg'
        ];

        if (!in_array($fileExt, $allowedExtensions)) {
            return $this->respond([
                'success' => false,
                'message' => 'Format file tidak didukung. Harap unggah berkas DED, CAD, BIM, atau Dokumen yang valid.'
            ], 400);
        }

        $projectName = $this->request->getPost('name') ?? 'Proyek BOQ Otomatis';
        $clientName  = $this->request->getPost('client') ?? 'Klien Internal';
        $url         = $this->getPythonBaseUrl('https://crashyet-burnout-predict.hf.space') . '/api/rab/analyze-image';

        $this->closeSession();

        try {
            $postMime = $file->getClientMimeType() ?: 'application/octet-stream';
            $curlFile = new CURLFile($file->getTempName(), $postMime, $file->getClientName());

            $response = Services::curlrequest()->post($url, [
                'multipart'   => [
                    'ded_file' => $curlFile,
                    'name'     => $projectName,
                    'client'   => $clientName,
                ],
                'http_errors' => false,
                'timeout'     => 1200,
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

    // POST /api/rab/analyze-prompt: Estimasi RAB berbasis prompt teks konsep desain
    public function analyzePrompt()
    {
        $json        = $this->request->getJSON(true);
        $projectName = $json['name'] ?? $this->request->getPost('name') ?? 'Konsep Desain Rumah';
        $clientName  = $json['client'] ?? $this->request->getPost('client') ?? 'Client';
        $promptText  = $json['prompt'] ?? $this->request->getPost('prompt') ?? '';

        if (empty(trim($promptText))) {
            return $this->respond(['success' => false, 'message' => 'Deskripsi konsep rumah / prompt tidak boleh kosong.'], 400);
        }

        $url = $this->getPythonBaseUrl('https://crashyet-burnout-predict.hf.space') . '/api/rab/analyze-prompt';
        $this->closeSession();

        try {
            $response = Services::curlrequest()->post($url, [
                'json'        => ['name' => $projectName, 'client' => $clientName, 'prompt' => $promptText],
                'headers'     => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'http_errors' => false,
                'timeout'     => 1200,
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

    // GET/POST /api/projects/(:segment)/audit: Pindai anomali volume & kelengkapan pemetaan item RAB proyek
    public function auditProject($projectUuidOrId = null)
    {
        $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->respond(['success' => false, 'message' => 'Proyek tidak ditemukan.'], 404);
        }

        $db        = Database::connect();
        $latestRun = $db->table('estimation_runs')->where('project_id', $project['id'])->orderBy('id', 'DESC')->get()->getRowArray();
        $sections  = [];

        if ($latestRun) {
            $secRows = $db->table('wbs_sections')->where('run_id', $latestRun['id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();
            foreach ($secRows as $sec) {
                $sec['items'] = $db->table('estimation_items')->where('section_id', $sec['id'])->orderBy('item_no', 'ASC')->get()->getResultArray();
                $sections[]   = $sec;
            }
        }

        $totalItems = 0; $criticalCount = 0; $warningCount = 0; $anomalies = [];

        foreach ($sections as $sec) {
            foreach ($sec['items'] as $it) {
                $totalItems++;
                $name   = !empty($it['ahsp_name']) ? $it['ahsp_name'] : $it['item_name'];
                $vol    = (float) $it['volume'];
                $price  = (float) $it['unit_price'];
                $status = $it['ahsp_status'] ?? 'unmapped';

                if ($vol <= 0) {
                    $criticalCount++;
                    $anomalies[] = [
                        'item_id'   => $it['id'],
                        'item_name' => $name,
                        'type'      => 'critical',
                        'subtitle'  => 'Anomali Volume 0.00 ' . ($it['unit'] ?: 'm2'),
                        'reason'    => 'Item pekerjaan ini bernilai 0.00 ' . ($it['unit'] ?: 'm2') . ' padahal terdapat rincian pekerjaan struktur.',
                    ];
                } elseif ($status === 'unmapped' || $price <= 0) {
                    $warningCount++;
                    $anomalies[] = [
                        'item_id'   => $it['id'],
                        'item_name' => $name,
                        'type'      => 'warning',
                        'subtitle'  => 'Kelengkapan Item AHSP',
                        'reason'    => 'Item alas/rincian pekerjaan belum dipetakan secara lengkap ke standar AHSP.',
                    ];
                }
            }
        }

        $normalCount   = max(0, $totalItems - ($criticalCount + $warningCount));
        $normalPercent = $totalItems > 0 ? round(($normalCount / $totalItems) * 100) : 100;

        return $this->respond([
            'success'   => true,
            'summary'   => [
                'total'             => $totalItems,
                'critical'          => $criticalCount,
                'warning'           => $warningCount,
                'normal'            => $normalCount,
                'normal_percentage' => $normalPercent,
            ],
            'anomalies' => $anomalies,
        ]);
    }

    // GET /api/projects/(:segment)/chat-history: Ambil riwayat chat konsultasi AI proyek
    public function getChatHistory($projectUuidOrId = null)
    {
        $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->respond(['success' => false, 'message' => 'Proyek tidak ditemukan.'], 404);
        }

        $history   = Database::connect()->table('ai_chat_histories')->where('project_id', $project['id'])->orderBy('id', 'ASC')->get()->getResultArray();
        $formatted = array_map(function ($row) use ($project) {
            $actions = null;
            if (!empty($row['actions_data'])) {
                $decoded = json_decode($row['actions_data'], true);
                if (is_array($decoded)) {
                    $actions = $this->normalizeActionsForProject($project['id'], $decoded);
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

        return $this->respond(['success' => true, 'messages' => $formatted]);
    }

    // PATCH /api/projects/(:segment)/chat-history/(:num)/applied: Tandai usulan perubahan aksi sudah diterapkan
    public function markActionApplied($projectUuidOrId = null, $historyId = null)
    {
        $json       = $this->request->getJSON(true) ?: [];
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        if (!empty($json['actions']) && is_array($json['actions'])) {
            $updateData['actions_data'] = json_encode($json['actions']);
            $allApplied                 = true;
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

        Database::connect()->table('ai_chat_histories')->where('id', (int) $historyId)->update($updateData);

        return $this->respond(['success' => true, 'message' => 'Status usulan perubahan berhasil diperbarui.']);
    }

    // DELETE /api/projects/(:segment)/chat-history: Bersihkan seluruh riwayat chat konsultasi proyek
    public function clearChatHistory($projectUuidOrId = null)
    {
        $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->failNotFound('Proyek tidak ditemukan.');
        }

        Database::connect()->table('ai_chat_histories')->where('project_id', $project['id'])->delete();

        return $this->respond(['success' => true, 'message' => 'Riwayat konsultasi AI berhasil dibersihkan.']);
    }

    // POST /api/projects/(:segment)/chat: Konsultasi AI Co-Pilot dan simpan riwayat percakapan ke DB
    public function chatProject($projectUuidOrId = null)
    {
        $json   = $this->request->getJSON(true);
        $prompt = trim($json['prompt'] ?? $this->request->getPost('prompt') ?? '');

        if (empty($prompt)) {
            return $this->respond(['success' => false, 'message' => 'Pertanyaan tidak boleh kosong.'], 400);
        }

        $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
        if (!$project) {
            return $this->respond(['success' => false, 'message' => 'Proyek tidak ditemukan.'], 404);
        }

        $db  = Database::connect();
        $now = date('Y-m-d H:i:s');

        // Simpan prompt pertanyaan user ke riwayat chat
        $db->table('ai_chat_histories')->insert([
            'project_id' => $project['id'],
            'sender'     => 'user',
            'message'    => $prompt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $urls       = $this->getPythonBaseUrls();
        $this->closeSession();
        $lastError  = 'Tidak dapat terhubung ke AI Agent.';
        $lastStatus = 502;

        foreach ($urls as $baseUrl) {
            $url = $baseUrl . '/api/v2/ai/rab-agent';
            try {
                $response = Services::curlrequest()->post($url, [
                    'json'            => [
                        'project_id'      => (string) $project['id'],
                        'prompt'          => $prompt,
                        'project_context' => $this->buildProjectContext($project),
                        'items'           => $json['items'] ?? [],
                    ],
                    'headers'         => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'http_errors'     => false,
                    'timeout'         => 60,
                    'connect_timeout' => 10,
                ]);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    $bodyData = json_decode($response->getBody(), true);
                    $reply    = $bodyData['reply_message'] ?? ($bodyData['reply'] ?? '');
                    if (!empty($reply)) {
                        $actions = $this->normalizeActionsForProject($project['id'], $bodyData['actions'] ?? []);
                        $db->table('ai_chat_histories')->insert([
                            'project_id'   => $project['id'],
                            'sender'       => 'ai',
                            'message'      => $reply,
                            'actions_data' => !empty($actions) ? json_encode($actions) : null,
                            'cost_impact'  => (float) ($bodyData['cost_impact'] ?? 0),
                            'is_applied'   => 0,
                            'created_at'   => date('Y-m-d H:i:s'),
                            'updated_at'   => date('Y-m-d H:i:s'),
                        ]);

                        return $this->respond([
                            'success'     => true,
                            'reply'       => $reply,
                            'actions'     => $actions,
                            'cost_impact' => $bodyData['cost_impact'] ?? 0,
                            'history_id'  => (int) $db->insertID(),
                        ]);
                    }
                }

                if ($statusCode >= 500) {
                    $decoded = json_decode($response->getBody(), true);
                    $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Status ' . $statusCode);
                    if (is_array($errMsg)) $errMsg = json_encode($errMsg);
                    $lastError  = "Layanan AI Agent ($url) gagal: " . $errMsg;
                    $lastStatus = $statusCode;
                    log_message('warning', "AI Agent ($url) merespons status $statusCode, mencoba fallback URL berikutnya...");
                    continue;
                }

                $decoded = json_decode($response->getBody(), true);
                $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Agent merespons kode ' . $statusCode);
                if (is_array($errMsg)) $errMsg = json_encode($errMsg);

                return $this->respond(['success' => false, 'message' => 'Layanan AI Agent gagal: ' . $errMsg], $statusCode);
            } catch (\Exception $e) {
                log_message('warning', "Python AI Agent ($url) error: " . $e->getMessage() . ", mencoba fallback URL berikutnya...");
                $lastError  = 'Tidak dapat terhubung ke AI Agent (' . $url . '): ' . $e->getMessage();
                $lastStatus = 502;
                continue;
            }
        }

        return $this->respond([
            'success' => false,
            'message' => $lastError
        ], $lastStatus >= 400 && $lastStatus < 600 ? $lastStatus : 502);
    }

    // POST /api/v2/ai/rab-audit: Proxy analisis audit RAB v2 ke microservice Python
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
            $project = (new ProjectModel())->findByIdOrUuid($projectUuidOrId);
            if ($project) {
                $json['project_id'] = (string) $project['id'];
                if (empty($json['project_context'])) {
                    $json['project_context'] = $this->buildProjectContext($project);
                }
            }
        }

        $urls       = $this->getPythonBaseUrls();
        $this->closeSession();
        $lastError  = 'Tidak dapat terhubung ke AI RAB Audit.';
        $lastStatus = 502;

        foreach ($urls as $baseUrl) {
            $url = $baseUrl . '/api/v2/ai/rab-audit';
            try {
                $response = Services::curlrequest()->post($url, [
                    'json'            => $json,
                    'headers'         => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'http_errors'     => false,
                    'timeout'         => 120,
                    'connect_timeout' => 10,
                ]);
                $statusCode = $response->getStatusCode();
                $body       = $response->getBody();

                if ($statusCode === 200) {
                    return $this->response->setStatusCode(200)->setContentType('application/json')->setBody($body);
                }

                // Jika server merespons 5xx (misal 502, 503, atau 530 Cloudflare), coba URL fallback berikutnya
                if ($statusCode >= 500) {
                    $decoded = json_decode($body, true);
                    $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Status ' . $statusCode);
                    if (is_array($errMsg)) $errMsg = json_encode($errMsg);
                    $lastError  = "Layanan AI RAB Audit ($url) gagal: " . $errMsg;
                    $lastStatus = $statusCode;
                    log_message('warning', "AI RAB Audit ($url) merespons status $statusCode, mencoba fallback URL berikutnya...");
                    continue;
                }

                // Jika error 4xx (validasi data client), jangan fallback tapi langsung kembalikan pesan
                $decoded = json_decode($body, true);
                $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Audit mengembalikan status ' . $statusCode);
                if (is_array($errMsg)) $errMsg = json_encode($errMsg);

                return $this->respond([
                    'status'  => 'error',
                    'message' => 'Layanan AI RAB Audit gagal: ' . $errMsg
                ], $statusCode);
            } catch (\Exception $e) {
                log_message('warning', "AI RAB Audit ($url) offline / unreachable: " . $e->getMessage() . ", mencoba fallback URL berikutnya...");
                $lastError  = 'Tidak dapat terhubung ke AI RAB Audit (' . $url . '): ' . $e->getMessage();
                $lastStatus = 502;
                continue;
            }
        }

        return $this->respond([
            'status'  => 'error',
            'message' => $lastError
        ], $lastStatus >= 400 && $lastStatus < 600 ? $lastStatus : 502);
    }

    // POST /api/v2/ai/rab-agent: Proxy Co-Pilot Agent v2 ke microservice Python dengan penyimpanan riwayat chat
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
            return $this->respond(['status' => 'error', 'message' => 'Instruksi / prompt untuk AI Co-Pilot tidak boleh kosong.'], 400);
        }

        $projectId = $json['project_id'] ?? $projectUuidOrId;
        $project   = (new ProjectModel())->findByIdOrUuid($projectId);
        $db        = Database::connect();
        $now       = date('Y-m-d H:i:s');

        // Simpan pesan user ke tabel ai_chat_histories dan siapkan context proyek
        if ($project) {
            $json['project_id'] = (string) $project['id'];
            if (empty($json['project_context'])) {
                $json['project_context'] = $this->buildProjectContext($project);
            }

            $db->table('ai_chat_histories')->insert([
                'project_id' => $project['id'],
                'sender'     => 'user',
                'message'    => $prompt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $urls       = $this->getPythonBaseUrls();
        $this->closeSession();
        $lastError  = 'Tidak dapat terhubung ke AI RAB Agent.';
        $lastStatus = 502;

        foreach ($urls as $baseUrl) {
            $url = $baseUrl . '/api/v2/ai/rab-agent';
            try {
                $response = Services::curlrequest()->post($url, [
                    'json'            => $json,
                    'headers'         => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'http_errors'     => false,
                    'timeout'         => 60,
                    'connect_timeout' => 10,
                ]);
                $statusCode = $response->getStatusCode();
                $body       = $response->getBody();

                if ($statusCode === 200) {
                    $pyBody = json_decode($body, true);
                    if ($pyBody && $project) {
                        $actions = $this->normalizeActionsForProject($project['id'], $pyBody['actions'] ?? []);
                        $db->table('ai_chat_histories')->insert([
                            'project_id'   => $project['id'],
                            'sender'       => 'ai',
                            'message'      => $pyBody['reply_message'] ?? ($pyBody['reply'] ?? ''),
                            'actions_data' => !empty($actions) ? json_encode($actions) : null,
                            'cost_impact'  => (float) ($pyBody['cost_impact'] ?? 0),
                            'is_applied'   => 0,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]);

                        $pyBody['history_id'] = (int) $db->insertID();
                        $pyBody['actions']    = $actions;
                        return $this->respond($pyBody);
                    }

                    return $this->response->setStatusCode(200)->setContentType('application/json')->setBody($body);
                }

                if ($statusCode >= 500) {
                    $decoded = json_decode($body, true);
                    $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Status ' . $statusCode);
                    if (is_array($errMsg)) $errMsg = json_encode($errMsg);
                    $lastError  = "Layanan AI RAB Agent ($url) gagal: " . $errMsg;
                    $lastStatus = $statusCode;
                    log_message('warning', "AI RAB Agent ($url) merespons status $statusCode, mencoba fallback URL berikutnya...");
                    continue;
                }

                $decoded = json_decode($body, true);
                $errMsg  = $decoded['detail'] ?? ($decoded['message'] ?? 'Layanan AI Agent mengembalikan status ' . $statusCode);
                if (is_array($errMsg)) $errMsg = json_encode($errMsg);

                return $this->respond([
                    'status'  => 'error',
                    'message' => 'Layanan AI RAB Agent gagal: ' . $errMsg
                ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 502);
            } catch (\Exception $e) {
                log_message('warning', "Python AI Agent API ($url) offline / unreachable: " . $e->getMessage() . ", mencoba fallback URL berikutnya...");
                $lastError  = 'Tidak dapat terhubung ke AI RAB Agent (' . $url . '): ' . $e->getMessage();
                $lastStatus = 502;
                continue;
            }
        }

        return $this->respond([
            'status'  => 'error',
            'message' => $lastError
        ], $lastStatus >= 400 && $lastStatus < 600 ? $lastStatus : 502);
    }

    // Normalisasi usulan perubahan action yang dihasilkan AI agar section_id dan kategori cocok dengan proyek
    private function normalizeActionsForProject($projectId, array $actions)
    {
        if (empty($actions)) {
            return [];
        }

        $db        = Database::connect();
        $latestRun = $db->table('estimation_runs')->where('project_id', $projectId)->orderBy('id', 'DESC')->get()->getRowArray();
        $sections  = [];

        if ($latestRun) {
            $sections = $db->table('wbs_sections')->where('run_id', $latestRun['id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();
        }

        foreach ($actions as &$act) {
            $act['project_id'] = $projectId;
            if (($act['action_type'] ?? '') === 'ADD_ITEM') {
                $targetSecId    = $act['target_section_id'] ?? null;
                $targetCategory = trim($act['target_category'] ?? ($act['changes']['category'] ?? ''));

                $matchedSec = null;
                if ($targetSecId) {
                    foreach ($sections as $s) {
                        if ((int)$s['id'] === (int)$targetSecId) {
                            $matchedSec = $s;
                            break;
                        }
                    }
                }

                if (!$matchedSec && !empty($targetCategory) && !empty($sections)) {
                    foreach ($sections as $s) {
                        if (stripos($s['name'], $targetCategory) !== false || stripos($targetCategory, $s['name']) !== false) {
                            $matchedSec = $s;
                            break;
                        }
                    }
                }

                if (!$matchedSec && !empty($sections)) {
                    $matchedSec = $sections[0];
                }

                if ($matchedSec) {
                    $act['target_section_id']   = (int) $matchedSec['id'];
                    $act['target_section_code'] = $matchedSec['code'];
                    if (empty($act['target_category'])) {
                        $act['target_category'] = $matchedSec['name'];
                    }
                }
            }
        }
        unset($act);

        return $actions;
    }
}
