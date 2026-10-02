<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class DocsController extends Controller
{
    /**
     * GET /docs and /api/docs
     * Modern interactive API Reference powered by Scalar
     */
    public function index()
    {
        return view('docs/scalar');
    }

    /**
     * GET /docs/swagger
     * Standard Swagger UI
     */
    public function swagger()
    {
        return view('docs/swagger');
    }

    /**
     * GET /api/openapi.json
     * OpenAPI 3.0.3 specification
     */
    public function openapi()
    {
        $openapi = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Estimator AI - CodeIgniter 4 REST API',
                'description' => "Dokumentasi resmi REST API Backend CodeIgniter 4 untuk Estimator Konstruksi & Rencana Anggaran Biaya (RAB).\n\n"
                    . "Mendukung manajemen proyek konstruksi, proxy analisis AI berkas CAD/BIM/DED dan prompt teks, "
                    . "audit integritas WBS, konsultasi interaktif AI Co-Pilot, serta katalog master data AHSP.",
                'version' => '2.1.0',
                'contact' => [
                    'name' => 'Beecons Estimator Team',
                    'url' => 'https://esti.eyi.my.id'
                ]
            ],
            'servers' => [
                [
                    'url' => '/',
                    'description' => 'Current Server (Relative Origin)'
                ],
                [
                    'url' => 'http://localhost:8080',
                    'description' => 'Local Development (Port 8080)'
                ],
                [
                    'url' => 'https://esti.eyi.my.id',
                    'description' => 'Production Tunnel'
                ]
            ],
            'tags' => [
                [
                    'name' => 'AI Analysis',
                    'description' => 'Endpoint analisis dokumen DED/CAD/BIM dan deskripsi prompt teks via AI'
                ],
                [
                    'name' => 'Projects',
                    'description' => 'Operasi CRUD master data proyek konstruksi'
                ],
                [
                    'name' => 'Estimation & WBS',
                    'description' => 'Penyimpanan dan manipulasi hasil estimasi, sections, dan item pekerjaan'
                ],
                [
                    'name' => 'AI Auditor & Co-Pilot',
                    'description' => 'Audit anomali RAB, chat konsultasi interaktif, dan rekomendasi perubahan WBS oleh AI'
                ],
                [
                    'name' => 'AHSP Master',
                    'description' => 'Katalog data master AHSP dan pencocokan semantic AI'
                ]
            ],
            'paths' => [
                // ==========================================
                // 1. AI Analysis Endpoints
                // ==========================================
                '/api/rab/analyze' => [
                    'post' => [
                        'tags' => ['AI Analysis'],
                        'summary' => 'Analisis Berkas DED atau Payload JSON',
                        'description' => "Menganalisis berkas DED (otomatis mendeteksi multipart berkas) atau meneruskan payload JSON ke AI service.",
                        'responses' => [
                            '200' => ['description' => 'Analisis berhasil diekstraksi'],
                            '400' => ['description' => 'Request body atau file tidak valid']
                        ]
                    ]
                ],
                '/api/rab/analyze-image' => [
                    'post' => [
                        'tags' => ['AI Analysis'],
                        'summary' => 'Analisis Berkas Desain Teknis (CAD / BIM / PDF / Gambar)',
                        'description' => "Mengunggah berkas teknis desain (maksimal 500 MB) untuk dianalisis oleh AI menjadi struktur WBS, estimasi volume, dan pemetaan AHSP awal.\n\n"
                            . "Format didukung: **BIM** (.rvt, .ifc, .skp, .nwd, .nwc), **CAD** (.dwg, .dxf, .svg, .plt), **Dokumen** (.pdf, .png, .jpg, .jpeg).",
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['ded_file'],
                                        'properties' => [
                                            'ded_file' => [
                                                'type' => 'string',
                                                'format' => 'binary',
                                                'description' => 'Berkas teknis CAD / BIM / PDF / Gambar'
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                                'example' => 'Pembangunan Ruko 3 Lantai',
                                                'description' => 'Nama proyek'
                                            ],
                                            'client' => [
                                                'type' => 'string',
                                                'example' => 'PT Graha Abadi',
                                                'description' => 'Nama pemilik proyek'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Analisis berhasil diekstraksi',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'success' => ['type' => 'boolean', 'example' => true],
                                                'project' => ['type' => 'object'],
                                                'summary_metrics' => ['type' => 'object'],
                                                'sections' => ['type' => 'array', 'items' => ['type' => 'object']]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '400' => ['description' => 'File tidak valid atau format tidak didukung'],
                            '413' => ['description' => 'Ukuran file melebihi batas upload web server']
                        ]
                    ]
                ],
                '/api/rab/analyze-prompt' => [
                    'post' => [
                        'tags' => ['AI Analysis'],
                        'summary' => 'Generasi Estimasi dari Prompt Konsep (Teks Bebas)',
                        'description' => 'Menghasilkan RAB lengkap dari deskripsi konsep bangunan dalam bahasa alami.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['prompt'],
                                        'properties' => [
                                            'name' => [
                                                'type' => 'string',
                                                'example' => 'Kos-Kosan 2 Lantai 10 Kamar'
                                            ],
                                            'client' => [
                                                'type' => 'string',
                                                'example' => 'Bpk. Hendra'
                                            ],
                                            'prompt' => [
                                                'type' => 'string',
                                                'example' => 'Rumah kos 2 lantai dengan 10 kamar tidur (masing-masing kamar mandi dalam ukuran 3x4 meter), struktur beton bertulang, atap baja ringan spandek, dan area parkir motor.'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Estimasi RAB dari prompt berhasil dibuat'],
                            '400' => ['description' => 'Prompt teks kosong']
                        ]
                    ]
                ],

                // ==========================================
                // 2. Projects Endpoints
                // ==========================================
                '/api/projects' => [
                    'get' => [
                        'tags' => ['Projects'],
                        'summary' => 'Daftar Semua Proyek',
                        'description' => 'Mengambil seluruh proyek beserta ringkasan estimasi run terakhir dan total anggaran.',
                        'responses' => [
                            '200' => [
                                'description' => 'Daftar proyek berhasil diambil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'integer', 'example' => 200],
                                                'success' => ['type' => 'boolean', 'example' => true],
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Project']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'tags' => ['Projects'],
                        'summary' => 'Buat Proyek Baru',
                        'description' => 'Mendaftarkan proyek baru dan mengembalikan ID serta UUID v4.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['title', 'client'],
                                        'properties' => [
                                            'title' => ['type' => 'string', 'example' => 'Villa Modern Lembang'],
                                            'client' => ['type' => 'string', 'example' => 'Ibu Ratna Dewi'],
                                            'location' => ['type' => 'string', 'example' => 'Bandung Barat'],
                                            'contractor_fee' => ['type' => 'number', 'format' => 'float', 'example' => 10.0],
                                            'ppn' => ['type' => 'number', 'format' => 'float', 'example' => 11.0],
                                            'status' => ['type' => 'string', 'example' => 'Perencanaan'],
                                            'summary' => ['type' => 'string', 'example' => 'Pembangunan villa peristirahatan keluarga']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Proyek berhasil dibuat'],
                            '422' => ['description' => 'Validasi gagal']
                        ]
                    ]
                ],
                '/api/projects/{id}' => [
                    'get' => [
                        'tags' => ['Projects'],
                        'summary' => 'Detail Proyek',
                        'description' => 'Mengambil detail proyek, riwayat runs estimasi, dan dokumen terkait menggunakan Integer ID atau UUID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'ID Integer (misal: 1) atau UUID (misal: 8cb67c7e-...)',
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Detail proyek ditemukan'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ],
                    'put' => [
                        'tags' => ['Projects'],
                        'summary' => 'Update Data Proyek (PUT)',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'client' => ['type' => 'string'],
                                            'location' => ['type' => 'string'],
                                            'contractor_fee' => ['type' => 'number'],
                                            'ppn' => ['type' => 'number'],
                                            'status' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Proyek berhasil diperbarui'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ],
                    'patch' => [
                        'tags' => ['Projects'],
                        'summary' => 'Update Sebagian Data Proyek (PATCH)',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Proyek berhasil diperbarui'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ],
                    'delete' => [
                        'tags' => ['Projects'],
                        'summary' => 'Hapus Proyek',
                        'description' => 'Menghapus proyek beserta riwayat estimasi runs dan item-itemnya.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Proyek berhasil dihapus'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ]
                ],

                // ==========================================
                // 3. Estimation Endpoints
                // ==========================================
                '/api/projects/{id}/save-estimation' => [
                    'post' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Simpan Hasil Estimasi AI ke Database',
                        'description' => 'Menyimpan payload JSON hasil estimasi (Sections, Items, Mapping AHSP) ke dalam tabel relasional proyek.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'sections' => ['type' => 'array', 'items' => ['type' => 'object']]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Estimasi berhasil disimpan'],
                            '400' => ['description' => 'Payload JSON tidak valid'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ]
                ],
                '/api/projects/{id}/latest-estimation' => [
                    'get' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Ambil Estimasi Terkini Proyek',
                        'description' => 'Mengambil sesi estimasi (run) terakhir suatu proyek dalam format WBS terstruktur.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Data estimasi terkini berhasil diambil'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ]
                ],
                '/api/projects/{id}/items' => [
                    'post' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Tambah Item Pekerjaan Baru ke Proyek',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['item_name'],
                                        'properties' => [
                                            'item_name' => ['type' => 'string', 'example' => 'Pemasangan Pintu Kayu Kamper'],
                                            'volume' => ['type' => 'number', 'example' => 4.0],
                                            'unit' => ['type' => 'string', 'example' => 'unit'],
                                            'unit_price' => ['type' => 'number', 'example' => 1250000],
                                            'ahsp_code' => ['type' => 'string', 'example' => 'A.4.6.1.1'],
                                            'target_category' => ['type' => 'string', 'example' => 'PEKERJAAN KUSEN DAN PINTU']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Item berhasil ditambahkan'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ]
                ],
                '/api/estimation-runs/{run_id}' => [
                    'get' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Ambil Sesi Estimasi Berdasarkan Run ID / UUID',
                        'parameters' => [
                            [
                                'name' => 'run_id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Data run estimasi berhasil diambil'],
                            '404' => ['description' => 'Run ID tidak ditemukan']
                        ]
                    ]
                ],
                '/api/estimation-items' => [
                    'post' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Tambah Baris Item Estimasi Baru (Mandiri)',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'project_id' => ['type' => 'string', 'example' => '1'],
                                            'section_id' => ['type' => 'integer', 'example' => 5],
                                            'item_name' => ['type' => 'string', 'example' => 'Plesteran Dinding'],
                                            'volume' => ['type' => 'number', 'example' => 100.0],
                                            'unit' => ['type' => 'string', 'example' => 'm2']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Item berhasil dibuat'],
                            '400' => ['description' => 'Input tidak valid']
                        ]
                    ]
                ],
                '/api/estimation-items/{item_id}' => [
                    'put' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Update Satu Baris Item Pekerjaan (PUT)',
                        'description' => 'Mengubah volume, unit price, atau kode AHSP pada item spesifik.',
                        'parameters' => [
                            [
                                'name' => 'item_id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'volume' => ['type' => 'number', 'example' => 50.0],
                                            'unit_price' => ['type' => 'number', 'example' => 85000],
                                            'ahsp_code' => ['type' => 'string', 'example' => 'A.4.1.1.1'],
                                            'ahsp_status' => ['type' => 'string', 'example' => 'mapped_high']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Item berhasil diupdate'],
                            '404' => ['description' => 'Item tidak ditemukan']
                        ]
                    ],
                    'patch' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Update Sebagian Atribut Item Pekerjaan (PATCH)',
                        'parameters' => [
                            [
                                'name' => 'item_id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Item berhasil diupdate'],
                            '404' => ['description' => 'Item tidak ditemukan']
                        ]
                    ],
                    'delete' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Hapus Item Pekerjaan',
                        'parameters' => [
                            [
                                'name' => 'item_id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Item berhasil dihapus'],
                            '404' => ['description' => 'Item tidak ditemukan']
                        ]
                    ]
                ],

                // ==========================================
                // 4. AI Auditor & Co-Pilot Endpoints
                // ==========================================
                '/api/projects/{id}/audit' => [
                    'get' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Audit Integritas Data WBS Proyek (Lokal)',
                        'description' => 'Pemeriksaan integritas item RAB terhadap anomali volume 0 dan kelengkapan mapping AHSP.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Hasil audit anomali WBS',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/AuditResult']
                                    ]
                                ]
                            ],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ],
                    'post' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Audit Integritas Data WBS Proyek (POST)',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Hasil audit anomali WBS']
                        ]
                    ]
                ],
                '/api/projects/{id}/ai-audit' => [
                    'post' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Audit Cerdas Berbasis AI LLM (Konteks Proyek Terisi Otomatis)',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Hasil audit AI berhasil diterima'],
                            '502' => ['description' => 'Layanan AI offline']
                        ]
                    ]
                ],
                '/api/projects/{id}/chat' => [
                    'post' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Konsultasi Interaktif Chat AI RAB & Usulan Perubahan WBS',
                        'description' => 'Mengajukan pertanyaan atau perintah modifikasi RAB. Jawaban dan aksi otomatis disimpan ke riwayat konsultasi.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['prompt'],
                                        'properties' => [
                                            'prompt' => [
                                                'type' => 'string',
                                                'example' => 'Ganti lantai keramik ke granit 60x60 cm dan hitung dampaknya.'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Respon AI beserta usulan perubahan WBS',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'success' => ['type' => 'boolean', 'example' => true],
                                                'reply' => ['type' => 'string'],
                                                'cost_impact' => ['type' => 'number', 'example' => 14500000],
                                                'history_id' => ['type' => 'integer', 'example' => 12],
                                                'actions' => ['type' => 'array', 'items' => ['type' => 'object']]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '400' => ['description' => 'Prompt kosong'],
                            '404' => ['description' => 'Proyek tidak ditemukan']
                        ]
                    ]
                ],
                '/api/projects/{id}/chat-history' => [
                    'get' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Ambil Riwayat Konsultasi Chat AI Proyek',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Daftar riwayat percakapan']
                        ]
                    ],
                    'delete' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Hapus Seluruh Riwayat Konsultasi Chat AI Proyek',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Riwayat berhasil dibersihkan']
                        ]
                    ]
                ],
                '/api/projects/{id}/chat-history/{history_id}/applied' => [
                    'patch' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Tandai Status Usulan Aksi AI Diterapkan / Belum',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'history_id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'is_applied' => ['type' => 'integer', 'example' => 1]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Status usulan perubahan diperbarui']
                        ]
                    ]
                ],
                '/api/v2/ai/rab-audit' => [
                    'post' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Proxy Langsung Stateless ke Layanan AI RAB Audit',
                        'responses' => [
                            '200' => ['description' => 'Audit berhasil'],
                            '502' => ['description' => 'Layanan AI offline']
                        ]
                    ]
                ],
                '/api/v2/ai/rab-agent' => [
                    'post' => [
                        'tags' => ['AI Auditor & Co-Pilot'],
                        'summary' => 'Proxy Langsung Stateless ke Layanan AI Co-Pilot Agent',
                        'responses' => [
                            '200' => ['description' => 'Respon agent berhasil diterima'],
                            '502' => ['description' => 'Layanan AI offline']
                        ]
                    ]
                ],

                // ==========================================
                // 5. AHSP Master Endpoints
                // ==========================================
                '/api/ahsp/list' => [
                    'get' => [
                        'tags' => ['AHSP Master'],
                        'summary' => 'Katalog Data Master AHSP',
                        'parameters' => [
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20]],
                            ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Daftar master AHSP berhasil diambil']
                        ]
                    ]
                ],
                '/api/ahsp/search' => [
                    'get' => [
                        'tags' => ['AHSP Master'],
                        'summary' => 'Pencarian Semantic AI Rekomendasi AHSP',
                        'parameters' => [
                            ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string'], 'example' => 'galian tanah keras'],
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 10]]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Rekomendasi pencocokan AHSP']
                        ]
                    ]
                ],
                '/api/ahsp/map-item' => [
                    'post' => [
                        'tags' => ['AHSP Master'],
                        'summary' => 'Pemetaan Otomatis Satu Item ke AHSP',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['item_name'],
                                        'properties' => [
                                            'item_name' => ['type' => 'string', 'example' => 'Plesteran dinding bata tebal 15mm 1:4'],
                                            'item_unit' => ['type' => 'string', 'example' => 'm2']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Hasil pemetaan AHSP berhasil ditemukan']
                        ]
                    ]
                ],
                '/api/ahsp/stats' => [
                    'get' => [
                        'tags' => ['AHSP Master'],
                        'summary' => 'Statistik Database Master AHSP',
                        'responses' => [
                            '200' => ['description' => 'Statistik total item dan kategori AHSP']
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'Project' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'uuid' => ['type' => 'string', 'example' => '8cb67c7e-e17f-4ea6-8aa2-d7b3cf68ea4c'],
                            'title' => ['type' => 'string', 'example' => 'Pembangunan Rumah 2 Lantai'],
                            'client' => ['type' => 'string', 'example' => 'PT Graha Abadi'],
                            'location' => ['type' => 'string', 'example' => 'Jakarta Selatan'],
                            'contractor_fee' => ['type' => 'number', 'example' => 10.0],
                            'ppn' => ['type' => 'number', 'example' => 11.0],
                            'status' => ['type' => 'string', 'example' => 'Perencanaan'],
                            'total_budget' => ['type' => 'number', 'example' => 450000000.0]
                        ]
                    ],
                    'AuditResult' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'summary' => [
                                'type' => 'object',
                                'properties' => [
                                    'total' => ['type' => 'integer', 'example' => 35],
                                    'critical' => ['type' => 'integer', 'example' => 1],
                                    'warning' => ['type' => 'integer', 'example' => 2],
                                    'normal' => ['type' => 'integer', 'example' => 32],
                                    'normal_percentage' => ['type' => 'integer', 'example' => 91]
                                ]
                            ],
                            'anomalies' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'item_id' => ['type' => 'integer'],
                                        'item_name' => ['type' => 'string'],
                                        'type' => ['type' => 'string', 'example' => 'critical'],
                                        'subtitle' => ['type' => 'string'],
                                        'reason' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->response
            ->setContentType('application/json')
            ->setJSON($openapi);
    }
}
