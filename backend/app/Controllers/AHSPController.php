<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AHSPController extends ResourceController
{
    protected function getPythonBaseUrl(): string
    {
        $envUrl = env('PYTHON_API_URL') ?: 'http://localhost:8200';
        return rtrim($envUrl, '/');
    }

    /**
     * Proxy GET /api/ahsp/list
     * Mendukung query parameters: page, limit, search
     */
    public function list()
    {
        $queryString = $this->request->getUri()->getQuery();
        $targetUrl = $this->getPythonBaseUrl() . '/api/ahsp/list' . ($queryString ? '?' . $queryString : '');

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($targetUrl, [
                'http_errors' => false,
                'timeout'     => 30
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke AHSP AI service.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy GET /api/ahsp/search
     * Mendukung query parameters: q, limit
     */
    public function search()
    {
        $queryString = $this->request->getUri()->getQuery();
        $targetUrl = $this->getPythonBaseUrl() . '/api/ahsp/search' . ($queryString ? '?' . $queryString : '');

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($targetUrl, [
                'http_errors' => false,
                'timeout'     => 30
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Gagal melakukan pencarian AHSP.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy POST /api/ahsp/map-item
     * Request body: JSON {"item_name": "...", "item_unit": "..."}
     */
    public function mapItem()
    {
        $json = $this->request->getJSON(true);
        $targetUrl = $this->getPythonBaseUrl() . '/api/ahsp/map-item';

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->post($targetUrl, [
                'json'        => $json,
                'headers'     => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json'
                ],
                'http_errors' => false,
                'timeout'     => 30
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Gagal memetakan item ke AHSP.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy GET /api/ahsp/stats
     */
    public function stats()
    {
        $targetUrl = $this->getPythonBaseUrl() . '/api/ahsp/stats';
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($targetUrl, [
                'http_errors' => false,
                'timeout'     => 15
            ]);

            return $this->response
                ->setStatusCode($response->getStatusCode())
                ->setContentType('application/json')
                ->setBody($response->getBody());
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Gagal mengambil statistik AHSP.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
