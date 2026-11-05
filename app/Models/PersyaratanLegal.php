<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersyaratanLegal extends Model
{
    use HasFactory;

    protected $table = 'persyaratan_legal';

    public $timestamps = false;
    protected $casts = ['status_berkas_dinamis' => 'array'];

    protected $fillable = [
        'id_customer',
        'IPH',
        'SHGB',
        'SSP',
        'BPHTB',
        'SIKUMBANG',
        'DAFTAR_SIKASEP',
        'FOTO_SIKASEP',
        'TRILOGI',
        'catatan_kekurangan',
        'percakapan_wa',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function statusJenisBerkas(JenisBerkasPengajuan $jenis): int
    {
        // Kolom lama tetap dipakai agar status pada modul Akad selalu sinkron.
        if ($jenis->kolom_lama) {
            return (int) $this->getAttribute($jenis->kolom_lama);
        }

        return (int) (($this->status_berkas_dinamis ?? [])[$jenis->id] ?? 0);
    }

    public function simpanStatusBerkas($jenisBerkas, array $status): void
    {
        $values = $this->status_berkas_dinamis ?? [];
        foreach ($jenisBerkas as $jenis) {
            $value = (int) $status[$jenis->id];
            if ($jenis->kolom_lama) {
                $this->setAttribute($jenis->kolom_lama, $value);
            } else {
                $values[$jenis->id] = $value;
            }
        }
        $this->status_berkas_dinamis = $values;
        $this->save();
    }
}
