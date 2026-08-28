<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class RabController extends ResourceController
{
    public function analyze()
    {
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
                'http_errors' => false
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
        // 1. Ambil file dari request CodeIgniter
        $file = $this->request->getFile('ded_file');

        if (!$file) {
            return $this->respond([
                'success' => false,
                'message' => 'File tidak ditemukan di request.'
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
}
