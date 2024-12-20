<?php

namespace App\Models;

use CodeIgniter\Model;

class PesertaDidikModel extends Model
{
    protected $table      = 'peserta_didik';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'deleted_at',
        'peserta_didik_id',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'nisn',
        'nik',
        'agama',
    ];
}
