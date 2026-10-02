<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AhspItemSeeder extends Seeder
{
    public function run()
    {
        $possiblePaths = [
            ROOTPATH . '../api_v2/ahsp/Item Pekerjaan CK.xlsx',
            FCPATH . '../../api_v2/ahsp/Item Pekerjaan CK.xlsx',
            '/home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/ahsp/Item Pekerjaan CK.xlsx',
        ];

        $excelPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $excelPath = realpath($path);
                break;
            }
        }

        if (!$excelPath || !file_exists($excelPath)) {
            echo "Excel file not found at: " . implode(', ', $possiblePaths) . "\n";
            return;
        }

        echo "Reading AHSP data from Excel: {$excelPath}...\n";

        $reader = IOFactory::createReaderForFile($excelPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Range A2:D{highestRow} (Row 1 is header: id_pekerjaan, nama_pekerjaan, satuan, harga_satuan)
        $rows = $sheet->rangeToArray('A2:D' . $highestRow, null, false, false, false);
        if (empty($rows)) {
            echo "No data found in Excel sheet.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $items = [];

        foreach ($rows as $row) {
            $idPekerjaan = trim((string)($row[0] ?? ''));
            $namaPekerjaan = trim((string)($row[1] ?? ''));
            $satuan = trim((string)($row[2] ?? ''));
            $hargaSatuan = (float)($row[3] ?? 0.0);

            if ($idPekerjaan === '' && $namaPekerjaan === '') {
                continue;
            }

            $items[] = [
                'id_pekerjaan'   => $idPekerjaan,
                'nama_pekerjaan' => $namaPekerjaan,
                'satuan'         => $satuan,
                'harga_satuan'   => $hargaSatuan,
                'sumber'         => 'CK',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        echo "Parsed " . count($items) . " AHSP items from Excel.\n";

        $builder = $this->db->table('ahsp_items');

        // Clear existing ahsp_items before re-seeding
        echo "Clearing existing ahsp_items...\n";
        $builder->emptyTable();

        echo "Seeding " . count($items) . " items into database...\n";
        $chunks = array_chunk($items, 250);
        foreach ($chunks as $idx => $chunk) {
            $builder->insertBatch($chunk);
            echo "  Inserted batch " . ($idx + 1) . " / " . count($chunks) . "\n";
        }

        echo "AHSP items seeding from Excel successfully completed!\n";
    }
}
