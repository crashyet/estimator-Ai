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
                    . "serta katalog master data AHSP (Analisis Harga Satuan Pekerjaan).",
                'version' => '2.0.0',
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
                    'url' => 'http://127.0.0.1:80',
                    'description' => 'Local Direct (Port 80/8080)'
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
                    'name' => 'AHSP Master',
                    'description' => 'Katalog data master AHSP dan pencocokan semantic AI'
                ]
            ],
            'paths' => [
                // 1. AI Analysis Endpoints
                '/api/rab/analyze' => [
                    'post' => [
                        'tags' => ['AI Analysis'],
                        'summary' => 'Analisis Berkas DED / CAD / BIM / PDF / Gambar',
                        'description' => "Mengunggah berkas teknis desain (maksimal 500 MB) untuk dianalisis oleh AI menjadi struktur WBS, estimasi volume, dan pemetaan AHSP awal.\n\n"
                            . "Format didukung: **BIM** (.rvt, .ifc, .skp, .nwd), **CAD** (.dwg, .dxf, .svg), **Dokumen** (.pdf, .png, .jpg).",
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
                                            ],
                                            'project_id' => [
                                                'type' => 'string',
                                                'example' => '1',
                                                'description' => 'ID atau UUID proyek (opsional)'
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
                                                'anggaran' => ['type' => 'array', 'items' => ['type' => 'object']]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '400' => ['description' => 'File tidak valid atau format tidak didukung'],
                            '413' => ['description' => 'Ukuran file melebihi batas 500 MB']
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

                // 2. Projects Endpoints
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
                        'summary' => 'Update Data Proyek',
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

                // 3. Estimation Endpoints
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
                            '200' => ['description' => 'Estimasi berhasil disimpan'],
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
                '/api/estimation-items/{item_id}' => [
                    'put' => [
                        'tags' => ['Estimation & WBS'],
                        'summary' => 'Update Satu Baris Item Pekerjaan',
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

                // 4. AHSP Master Endpoints
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
                    ]
                ]
            ]
        ];

        return $this->response
            ->setContentType('application/json')
            ->setJSON($openapi);
    }
}
