<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class DocsController extends Controller
{
    // GET /docs dan /api/docs: Dokumentasi API interaktif modern berbasis Scalar UI
    public function index()
    {
        return view('docs/scalar');
    }

    // GET /docs/swagger: Dokumentasi API antarmuka standar Swagger UI
    public function swagger()
    {
        return view('docs/swagger');
    }

    // GET /api/openapi.json: Sajikan spesifikasi OpenAPI 3.0.3 dari berkas static openapi.json
    public function openapi()
    {
        $filePath = FCPATH . 'openapi.json';
        if (is_file($filePath)) {
            return $this->response
                ->setContentType('application/json')
                ->setBody(file_get_contents($filePath));
        }

        return $this->response
            ->setStatusCode(404)
            ->setContentType('application/json')
            ->setJSON(['status' => 404, 'error' => 'Berkas openapi.json tidak ditemukan.']);
    }
}
