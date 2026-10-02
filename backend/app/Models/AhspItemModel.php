<?php

namespace App\Models;

use CodeIgniter\Model;

class AhspItemModel extends Model
{
    protected $table            = 'ahsp_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'id_pekerjaan',
        'nama_pekerjaan',
        'satuan',
        'harga_satuan',
        'sumber',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Find harga_satuan by id_pekerjaan (ahsp_code) or nama_pekerjaan
     */
    public function getHargaSatuanByCodeOrName(?string $code, ?string $name = null): float
    {
        $code = trim($code ?? '');
        if (!empty($code) && $code !== '-') {
            $row = $this->where('id_pekerjaan', $code)->first();
            if ($row && isset($row['harga_satuan'])) {
                return (float) $row['harga_satuan'];
            }
        }

        $name = trim($name ?? '');
        if (!empty($name)) {
            $row = $this->where('nama_pekerjaan', $name)->first();
            if (!$row) {
                $row = $this->like('nama_pekerjaan', $name)->first();
            }
            if ($row && isset($row['harga_satuan'])) {
                return (float) $row['harga_satuan'];
            }
        }

        return 0.0;
    }
}