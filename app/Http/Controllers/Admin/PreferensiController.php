<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreferensiNotifikasiRequest;
use App\Http\Requests\PreferensiPublikasiRequest;
use App\Models\Preferensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menyimpan preferensi admin: tema tampilan, saklar notifikasi, dan aturan
 * publikasi.
 *
 * Ketiganya menulis ke satu tabel yang sama (tb_preferensi), tapi lewat tiga
 * form yang berbeda, dan setiap form hanya boleh mengubah kolomnya sendiri:
 *
 *   - tema        : satu nilai string;
 *   - notifikasi  : empat boolean, satu untuk tiap jenis notifikasi yang
 *                   punya sumber nyata di aplikasi ini;
 *   - publikasi   : satu status bawaan dan satu boolean konfirmasi.
 *
 * Pemisahan itu bukan sekadar susun rapih. Kalau form notifikasi ikut bisa
 * menulis kolom tema, mematikan satu saklar akan diam-diam mengubah tema
 * admin juga.
 *
 * Setiap aksi selesai dengan mengembalikan admin ke /admin/pengaturan, tempat
 * semua saklarnya terlihat, jadi hasilnya bisa langsung diperiksa.
 */
class PreferensiController extends Controller
{
    /**
     * Simpan mode tampilan.
     *
     * Nilainya ditulis ke database dan sekaligus ke localStorage lewat
     * JavaScript: localStorage yang membuat tema langsung berlaku tanpa
     * menunggu render ulang, sedangkan kolom di database yang membuatnya ikut
     * berlaku di perangkat lain dan di halaman yang sudah terbuka sebelum tema
     * itu disimpan.
     *
     * Nilai yang tidak dikenal ditolak dan jatuh ke terang, jadi form yang
     * mengirim isian aneh tidak bisa membuat area admin kehilangan warna.
     */
    public function tema(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tema' => ['required', 'string', Rule::in([
                Preferensi::TEMA_TERANG,
                Preferensi::TEMA_GELAP,
            ])],
        ], [
            'tema.required' => 'Mode tampilan wajib dipilih.',
            'tema.in' => 'Mode tampilan harus Terang atau Gelap.',
        ]);

        $tema = Preferensi::ambil($request->user())->simpanTema($data['tema']);

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Mode tampilan berhasil disimpan.')
            ->with('suksesDetail', 'Mode '.($tema === Preferensi::TEMA_GELAP ? 'gelap' : 'terang').' dipakai di seluruh area admin.');
    }

    /**
     * Simpan saklar jenis notifikasi yang diterima admin.
     *
     * Pengaturan ini hanya menentukan kabar apa yang muncul di lonceng admin.
     * Notifikasi untuk pengguna saat admin menerbitkan konten dibuat oleh
     * App\Support\NotifikasiKonten dan tidak pernah membaca tabel preferensi,
     * jadi mematikan saklar di sini tidak menyebabkan pengguna berhenti
     * menerima notifikasi publikasi.
     */
    public function notifikasi(PreferensiNotifikasiRequest $request): RedirectResponse
    {
        Preferensi::ambil($request->user())->fill($request->nilai())->save();

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Pengaturan notifikasi berhasil disimpan.')
            ->with('suksesDetail', 'Jenis notifikasi yang dimatikan tidak lagi muncul di lonceng.');
    }

    /**
     * Simpan aturan publikasi konten.
     *
     * Dua isiannya benar-benar dipakai aplikasi:
     *
     *   - status_konten_default dipakai form admin saat ia menyimpan konten
     *     tanpa memilih status secara eksplisit;
     *   - konfirmasi_publikasi membuat langkah "Publish" pada daftar Konten
     *     Pembelajaran membuka dialog konfirmasi lebih dulu.
     *
     * Keduanya dibaca dari Preferensi oleh pemanggilnya masing-masing, bukan
     * disimpan lalu dibiarkan tidak dibaca.
     */
    public function publikasi(PreferensiPublikasiRequest $request): RedirectResponse
    {
        $preferensi = Preferensi::ambil($request->user());
        $preferensi->simpanStatusKonten((string) $request->input('status_konten_default'));
        $preferensi->fill([
            'konfirmasi_publikasi' => $request->boolean('konfirmasi_publikasi'),
        ])->save();

        return redirect()
            ->route('admin.pengaturan')
            ->with('sukses', 'Pengaturan publikasi berhasil disimpan.')
            ->with('suksesDetail', $preferensi->status_konten_default === 'draft'
                ? 'Konten baru akan disimpan sebagai Draft secara default.'
                : 'Konten baru akan langsung berstatus Published secara default.');
    }
}
