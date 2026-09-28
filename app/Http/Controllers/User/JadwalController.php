<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalIsianRequest;
use App\Models\Jadwal;
use App\Support\DaftarJadwal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Jadwal": daftar pelajaran per hari, lengkap dengan pemilih hari,
 * filter pelajaran, pencarian, kalender mini, dan pengelolaan jadwal sendiri.
 *
 * Halaman ini adalah tujuan tombol "Lihat Semua" di panel "Jadwal Hari Ini"
 * milik dashboard. Keduanya membaca data dari App\Support\DaftarJadwal,
 * jadi baris yang muncul di dashboard dijamin sama dengan yang muncul di sini
 * untuk tanggal yang sama.
 *
 * Data diambil dengan satu query: jadwalMinggu() menarik seluruh jadwal
 * pengguna sekali, lalu hari(), minggu(), bulan(), dan kategori() membaca dari
 * peta itu. Kalau tidak begitu, kalender sebulan penuh akan menjalankan
 * puluhan query.
 *
 * Seluruh state ada di query string (?tanggal, ?kategori, ?q, ?bulan),
 * tanpa JavaScript dan tanpa session: halaman tetap jalan kalau JS dimatikan,
 * dan URL-nya bisa disalin atau di-bookmark.
 *
 * Pengelolaan jadwal (edit dan hapus) ada di User\JadwalKelolaController,
 * dipisah karena selalu butuh cek kepemilikan. Tambah jadwal ada di sini
 * karena tidak butuh model jadwal.
 */
class JadwalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tanggal = DaftarJadwal::tanggalDariQuery($request->query('tanggal')) ?? now()->startOfDay();
        $bulan = DaftarJadwal::bulanDariQuery($request->query('bulan')) ?? $tanggal->copy()->startOfMonth();

        $kataKunci = trim((string) $request->query('q', ''));

        $peta = DaftarJadwal::jadwalMinggu($request->user()?->getKey());
        $kategori = $this->kategoriAktif($request->query('kategori'), $peta['hari']);

        $jadwal = DaftarJadwal::hari($peta['hari'], $tanggal, $kategori, $kataKunci);

        return view('user.jadwal', [
            'tanggal' => $tanggal,
            'jadwal' => $jadwal,
            'ringkasan' => DaftarJadwal::ringkasan($jadwal, $tanggal),
            'minggu' => DaftarJadwal::minggu($peta['hari'], $tanggal, $kategori, $kataKunci),
            'kalender' => DaftarJadwal::bulan($peta['hari'], $bulan, $kategori, $kataKunci),
            'kategori' => DaftarJadwal::kategori($peta['hari']),
            'kategoriAktif' => $kategori,
            'kataKunci' => $kataKunci,
            // Masih contoh jadwal, bukan milik pengguna. Halaman memakai ini
            // untuk memberi tahu dan menawarkan tombol menggantinya.
            'pakaiContoh' => $peta['contoh'],
        ]);
    }

    /**
     * Form tambah jadwal.
     *
     * Tanggal yang sedang dibaca di halaman jadwal ikut dibawa, jadi tombol
     * "Tambah Jadwal" di hari tertentu langsung membuka form untuk hari itu
     * dan setelah disimpan pengguna kembali ke hari tersebut.
     */
    public function create(Request $request): View
    {
        return view('user.jadwal-tambah', [
            'jadwal' => null,
            'hariAktif' => DaftarJadwal::tanggalDariQuery($request->query('tanggal'))?->dayOfWeek,
        ]);
    }

    public function store(JadwalIsianRequest $request): RedirectResponse
    {
        /*
         * Jadwal langsung dimiliki pengguna yang sedang login, bukan dari
         * field request, supaya isian "dibuat_oleh" palsu tidak bisa dipakai
         * untuk membuat jadwal atas nama orang lain.
         */
        $jadwal = new Jadwal([
            ...$request->isian(),
            'dibuat_oleh' => $request->user()->getKey(),
        ]);

        $jadwal->save();

        return redirect()
            ->route('user.jadwal', ['tanggal' => DaftarJadwal::tanggalDekat($jadwal->hari)])
            ->with('sukses', 'Jadwal "'.$jadwal->judul.'" berhasil ditambahkan.');
    }

    /**
     * Kategori dari query string, hanya kalau memang salah satu pelajaran
     * yang benar-benar dipakai di jadwal minggu ini. Nilai lain dianggap
     * "semua" supaya URL yang diketik manual tidak membuat halaman kosong
     * tanpa penjelasan.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $peta
     */
    private function kategoriAktif(mixed $nilai, array $peta): string
    {
        $nilai = is_string($nilai) ? trim($nilai) : '';

        if ($nilai === '') {
            return '';
        }

        foreach (DaftarJadwal::kategori($peta) as $item) {
            if ($item['slug'] === $nilai) {
                return $nilai;
            }
        }

        return '';
    }
}
