<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisBerkasPengajuan extends Model
{
    use SoftDeletes;

    protected $table = 'jenis_berkas_pengajuan';
    protected $fillable = ['nama', 'singkatan', 'urutan', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true)->orderBy('urutan')->orderBy('id');
    }
}
