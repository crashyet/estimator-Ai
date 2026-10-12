<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Config\Services;

class AHSPController extends ResourceController
{
    // Ambil base URL service Python dari environment dengan fallback localhost:8200
    protected function getPythonBaseUrl(): string
    {
        return rtrim(env('PYTHON_API_URL') ?: 'http://localhost:8200', '/');
    }

    // Helper proxy terpusat: meneruskan request ke service Python tanpa duplikasi cURL
    protected function proxyRequest(string $method, string $path, array $options = [], string $errorMsg = '', bool $withQuery = false)
    {
        $url = $this->getPythonBaseUrl() . $path;
        if ($withQuery && ($query = $this->request->getUri()->getQuery())) {
            $url .= '?' . $query;
        }

        try {
            // Eksekusi cURL request
            $response = Services::curlrequest()->request($method, $url, array_merge(['http_errors' => false], $options));

            // Teruskan response langsung dari service Python
            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());
        } catch (\Exception $e) {
            // Tangani error koneksi atau timeout ke service Python
            return $this->respond([
                'success' => false,
                'message' => $errorMsg,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/ahsp/list: Proxy daftar item AHSP (query: page, limit, search)
    public function list()
    {
        return $this->proxyRequest('GET', '/api/ahsp/list', ['timeout' => 30], 'Tidak dapat terhubung ke AHSP AI service.', true);
    }

    // GET /api/ahsp/search: Proxy pencarian semantic AHSP (query: q, limit)
    public function search()
    {
        return $this->proxyRequest('GET', '/api/ahsp/search', ['timeout' => 30], 'Gagal melakukan pencarian AHSP.', true);
    }

    // POST /api/ahsp/map-item: Proxy pemetaan item pekerjaan ke AHSP (body: item_name, item_unit)
    public function mapItem()
    {
        $options = [
            'json'    => $this->request->getJSON(true),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ],
            'timeout' => 30
        ];
        return $this->proxyRequest('POST', '/api/ahsp/map-item', $options, 'Gagal memetakan item ke AHSP.');
    }

    // GET /api/ahsp/stats: Proxy statistik master data AHSP
    public function stats()
    {
        return $this->proxyRequest('GET', '/api/ahsp/stats', ['timeout' => 15], 'Gagal mengambil statistik AHSP.');
    }
}
