<?php

namespace Tests\Feature;

use App\Http\Controllers\Legal\BerkasPengajuanController;
use App\Models\JenisBerkasPengajuan;
use App\Models\PersyaratanLegal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JenisBerkasPengajuanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('jenis_berkas_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('singkatan');
            $table->string('kolom_lama')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('persyaratan_legal', function (Blueprint $table) {
            $table->id();
            $table->integer('IPH')->default(0);
            $table->json('status_berkas_dinamis')->nullable();
            $table->text('catatan_kekurangan')->nullable();
            $table->string('percakapan_wa')->nullable();
        });
    }

    public function test_new_and_legacy_statuses_survive_renaming_and_deactivation(): void
    {
        $legacy = JenisBerkasPengajuan::create(['nama' => 'IPH', 'singkatan' => 'IPH', 'urutan' => 2, 'aktif' => true]);
        $legacy->kolom_lama = 'IPH';
        $legacy->save();
        $new = JenisBerkasPengajuan::create(['nama' => 'Berkas Tambahan', 'singkatan' => 'BT', 'urutan' => 1, 'aktif' => true]);
        $berkas = PersyaratanLegal::create(['IPH' => 1]);
        $this->assertSame(1, $berkas->statusJenisBerkas($legacy));
        $this->assertSame(0, $berkas->statusJenisBerkas($new));
        $this->assertSame([$new->id, $legacy->id], JenisBerkasPengajuan::aktif()->pluck('id')->all());

        $response = (new BerkasPengajuanController)->update(Request::create('/', 'POST', [
            'berkas' => [$legacy->id => '0', $new->id => '1'],
        ]), $berkas->id);
        $this->assertSame(200, $response->getStatusCode());
        $berkas->refresh();
        $this->assertSame(0, (int) $berkas->IPH);
        $this->assertSame(1, $berkas->statusJenisBerkas($new));

        $new->update(['singkatan' => 'BARU', 'aktif' => false]);
        $berkas->simpanStatusBerkas(JenisBerkasPengajuan::aktif()->get(), [$legacy->id => 1]);
        $berkas->refresh();
        $this->assertSame(1, $berkas->statusJenisBerkas($new));
        $this->assertSame(1, (int) $berkas->IPH);
        $this->assertSame([$legacy->id], JenisBerkasPengajuan::aktif()->pluck('id')->all());
        $new->update(['aktif' => true]);
        $this->assertSame(1, $berkas->statusJenisBerkas($new));
        $new->delete();
        $this->assertSame([$legacy->id], JenisBerkasPengajuan::aktif()->pluck('id')->all());
        $this->assertSame(1, (int) $berkas->status_berkas_dinamis[$new->id]);
    }

    public function test_dynamic_status_rejects_invalid_values(): void
    {
        $jenis = JenisBerkasPengajuan::create(['nama' => 'Tambahan', 'singkatan' => 'T', 'aktif' => true]);
        $berkas = PersyaratanLegal::create([]);
        $this->expectException(ValidationException::class);
        (new BerkasPengajuanController)->update(Request::create('/', 'POST', ['berkas' => [$jenis->id => '2']]), $berkas->id);
    }
}
