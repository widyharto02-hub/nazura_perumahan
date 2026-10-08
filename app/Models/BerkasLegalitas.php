<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerkasLegalitas extends Model
{
    protected $table = 'berkas_legalitas';

    protected $fillable = [
        'jenis',
        'keterangan',
        'lampiran',
    ];
}
