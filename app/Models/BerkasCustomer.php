<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BerkasCustomer extends Model
{
    use HasFactory;

    protected $table = 'berkas_customer';

    protected $fillable = [
        'tanggal',
        'id_customer',
        'nama_file',
        'keterangan',
        'lampiran',
    ];

    public $timestamps = false;

}
