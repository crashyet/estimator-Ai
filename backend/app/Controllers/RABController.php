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

        $pythonUrl = 'http://192.168.1.41:8200/api/rab/analyze-json'; 

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
                'error' => $e->getMessage()
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
                'error' => $file->getErrorString()
            ], 400);
        }

        // VALIDASI FILE 
        $fileExt  = strtolower($file->getClientExtension());
        $fileMime = $file->getMimeType();

        // Cek apakah ini benar-benar PDF
        $isPdfValid = ($fileExt === 'pdf' && $fileMime === 'application/pdf');

        // Cek apakah ini benar-benar DWG
        $dwgMimeTypes = [
            'image/vnd.dwg',
            'application/acad',
            'application/x-dwg',
            'image/x-dwg',
            'application/autocad_dwg',
            'application/octet-stream' 
        ];
        $isDwgValid = ($fileExt === 'dwg' && in_array($fileMime, $dwgMimeTypes));

        // Cek apakah ini benar-benar RVT (Revit)
        $rvtMimeTypes = [
            'application/rvt',
            'application/vnd.autodesk.revit',
            'application/octet-stream' // Sering kali file custom terbaca sebagai octet-stream
        ];
        $isRvtValid = ($fileExt === 'rvt' && in_array($fileMime, $rvtMimeTypes));

        // Jika BUKAN PDF, BUKAN DWG, DAN BUKAN RVT -> TOLAK
        if (!$isPdfValid && (!$isDwgValid) && (!$isRvtValid)) {
            return $this->respond([
                'success' => false,
                'message' => 'Format file tidak didukung. Harap gunakan file PDF, DWG, atau RVT DED yang sah.'
            ], 400);
        }

        // 2. Ambil parameter form
        $projectName = $this->request->getPost('name') ?? 'Proyek BOQ Otomatis';
        $clientName  = $this->request->getPost('client') ?? 'Klien Internal';

        // 3. Arahkan ke URL FastAPI Python
        $pythonUrl = 'http://192.168.1.41:8200/api/rab/analyze-image';

        $client = \Config\Services::curlrequest();

        try {
            // Bungkus file untuk dikirim via cURL
            $curlFile = new \CURLFile(
                $file->getTempName(),
                $file->getMimeType(),
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
                'timeout'     => 120    
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