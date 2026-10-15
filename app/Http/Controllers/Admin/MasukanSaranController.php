<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasukanSaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman "Masukan & Saran" di area admin: daftar seluruh kiriman
 * pengguna, lengkap dengan pencarian, filter status, dan detail tiap
 * kiriman.
 *
 * Halaman ini hanya membaca dan menyediakan satu perubahan: menandai
 * status tindak lanjut (baru, diproses, selesai). Penghapusan sengaja
 * tidak ada — kiriman pengguna adalah catatan yang sebaiknya tetap
 * tersimpan; kalau akun pengirimnya dihapus, barisnya ikut terhapus
 * lewat cascade di database.
 *
 * Lampiran disajikan lewat route terpisah yang dijaga middleware admin,
 * bukan tautan langsung ke disk publik, supaya berkasnya hanya bisa
 * dibuka dari halaman ini.
 */
class MasukanSaranController extends Controller
{
    /**
     * Kiriman per halaman.
     */
    private const PER_HALAMAN = 10;

    /**
     * Nilai tab status: semua | baru | diproses | selesai.
     *
     * Setelah "semua", nilai berikutnya persis nilai kolom "status", jadi
     * tidak ada tab yang bisa difilter tapi tidak ada di data. Labelnya
     * ditulis di view lewat MasukanSaran::labelStatus(), supaya controller
     * tidak ikut menentukan istilah UI.
     *
     * @var array<int, string>
     */
    private const STATUS = [
        'semua',
        MasukanSaran::STATUS_BARU,
        MasukanSaran::STATUS_DIPROSES,
        MasukanSaran::STATUS_SELESAI,
    ];

    public function __invoke(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));
        $status = $this->terpilih($request->query('status'), self::STATUS, 'semua');

        /*
         * Urutannya created_at lalu id, sama seperti daftar Pengguna: satu
         * halaman bisa memuat beberapa kiriman dengan waktu yang sama
         * persis, dan tanpa id sebagai pemutus, baris yang sama bisa muncul
         * di dua halaman atau dilewati.
         */
        $daftar = $this->saring(MasukanSaran::query(), $kataKunci, $status)
            ->with('pengguna')
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        return view('admin.masukan-saran', [
            'daftar' => $daftar,
            'kataKunci' => $kataKunci,
            'statusAktif' => $status,
            'tabStatus' => $this->tabStatus(),
            'statistik' => $this->statistik(),
            'detail' => $this->detail($daftar),
        ]);
    }

    /**
     * Perbarui status tindak lanjut satu kiriman.
     *
     * Nilai status dibatasi ke konstanta model, jadi nilai asing dari form
     * tidak bisa masuk ke kolomnya. Sesudah berhasil, admin dikembalikan ke
     * halaman daftar dengan filter yang sedang aktif (atau tanpa filter),
     * karena halaman detail berupa dialog di halaman yang sama.
     */
    public function updateStatus(Request $request, MasukanSaran $masukanSaran): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(MasukanSaran::STATUS)],
        ]);

        $masukanSaran->update(['status' => $data['status']]);

        return back()
            ->with('sukses', 'Status diperbarui')
            ->with(
                'suksesDetail',
                'Masukan "'.$masukanSaran->judul.'" ditandai '.MasukanSaran::labelStatus($data['status']).'.',
            );
    }

    /**
     * Sajikan lampiran masukan kepada admin.
     *
     * Berkasnya disimpan di disk publik (lihat App\Support\BerkasMasukan),
     * jadi tanpa route ini lampirannya sebenarnya bisa dibuka langsung.
     * Route ini yang menjadikan seluruh akses lampiran lewat jalur yang
     * dijaga middleware admin; nama berkas hasrat di disk tetap tidak
     * terduga, jadi tidak ada berkas yang bisa ditebak satu per satu.
     *
     * Nama berkasnya tidak dikembalikan sebagai unduhan paksa: lampiran bisa
     * berupa tangkapan layar yang lebih nyaman dilihat langsung di tab baru,
     * dan PDF pun bisa dipratinjau browser.
     */
    public function lampiran(MasukanSaran $masukanSaran): StreamedResponse
    {
        $path = (string) $masukanSaran->lampiran;

        abort_if($path === '' || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    /**
     * Query daftar dengan pencarian dan filter status sekaligus.
     */
    private function saring(Builder $query, string $kataKunci, string $status): Builder
    {
        $kataKunci = trim($kataKunci);

        if ($kataKunci !== '') {
            [$operator, $pola] = $this->polaCari($query->getConnection()->getDriverName(), $kataKunci);

            $query->where(function (Builder $isi) use ($operator, $pola): void {
                $isi->where('judul', $operator, $pola)
                    ->orWhereHas('pengguna', fn (Builder $pengguna) => $pengguna
                        ->where('nama', $operator, $pola)
                        ->orWhere('email', $operator, $pola));
            });
        }

        return $query->when(
            in_array($status, MasukanSaran::STATUS, true),
            fn (Builder $saring) => $saring->where('status', $status),
        );
    }

    /**
     * Operator LIKE sesuai driver dan pola yang menetralkan wildcard dari
     * pengguna, seragam dengan pencarian di halaman admin lain.
     *
     * @return array{0: string, 1: string}
     */
    private function polaCari(string $driver, string $kataKunci): array
    {
        $operator = $driver === 'pgsql' ? 'ilike' : 'like';

        return [$operator, '%'.addcslashes($kataKunci, '%_\\').'%'];
    }

    /**
     * Tab filter status, lengkap dengan jumlahnya.
     *
     * Jumlahnya dihitung dari seluruh tabel, bukan dari daftar yang sudah
     * tersaring, supaya angkanya tetap berarti sebagai gambaran keseluruhan
     * — sama seperti tab di halaman Pengguna.
     *
     * @return array<string, array{label: string, nilai: string, jumlah: int, href: string}>
     */
    private function tabStatus(): array
    {
        $jumlah = MasukanSaran::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $total = (int) $jumlah->sum();

        $tab = [];

        foreach (self::STATUS as $nilai) {
            $tab[$nilai] = [
                'label' => $nilai === 'semua' ? 'Semua' : MasukanSaran::labelStatus($nilai),
                'nilai' => $nilai,
                'jumlah' => $nilai === 'semua' ? $total : (int) ($jumlah[$nilai] ?? 0),
                'href' => route('admin.masukan-saran', ['status' => $nilai]),
            ];
        }

        return $tab;
    }

    /**
     * Angka kartu statistik, semuanya dihitung dari database dan tidak ikut
     * berubah saat filter atau pencarian sedang dipakai.
     *
     * @return array{total: int, baru: int, diproses: int, selesai: int}
     */
    private function statistik(): array
    {
        $jumlah = MasukanSaran::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return [
            'total' => (int) $jumlah->sum(),
            'baru' => (int) ($jumlah[MasukanSaran::STATUS_BARU] ?? 0),
            'diproses' => (int) ($jumlah[MasukanSaran::STATUS_DIPROSES] ?? 0),
            'selesai' => (int) ($jumlah[MasukanSaran::STATUS_SELESAI] ?? 0),
        ];
    }

    /**
     * Isi dialog detail, dikunci dengan id kiriman.
     *
     * Bentuknya map, bukan daftar, supaya JavaScript cukup mencari satu id:
     * satu dialog dipakai ulang untuk semua baris, dan tidak ada baris pun
     * yang menulis isi lampirannya sendiri di markup. Tautan lampiran dan
     * status disiapkan server supaya JavaScript tidak menyusun URL.
     *
     * @param  LengthAwarePaginator<int, MasukanSaran>  $daftar
     * @return array<int|string, array<string, mixed>>
     */
    private function detail($daftar): array
    {
        $hasil = [];

        foreach ($daftar as $masukan) {
            $status = (string) $masukan->status;
            $lampiran = filled($masukan->lampiran) ? (string) $masukan->lampiran : null;

            $hasil[$masukan->getKey()] = [
                'judul' => (string) $masukan->judul,
                'detail' => (string) $masukan->detail,
                'nama' => (string) ($masukan->pengguna?->nama ?? 'Pengguna dihapus'),
                'email' => (string) ($masukan->pengguna?->email ?? ''),
                'inisial' => $masukan->pengguna?->inisial() ?? '?',

                'dikirim' => $masukan->created_at?->translatedFormat('d M Y') ?? '-',
                'dikirim_jam' => $masukan->created_at?->format('H:i') ?? '',

                'status' => $status,
                'status_label' => MasukanSaran::labelStatus($status),
                'status_nada' => MasukanSaran::nadaStatus($status),

                'lampiran' => $lampiran,
                'lampiran_nama' => $lampiran === null ? null : basename($lampiran),
                'lampiran_url' => $lampiran === null
                    ? null
                    : route('admin.masukan-saran.lampiran', $masukan),

                'status_url' => route('admin.masukan-saran.status', $masukan),
            ];
        }

        return $hasil;
    }

    /**
     * Nilai dari query string, dipaksa ke salah satu yang diizinkan.
     *
     * Tanpa penjaga ini ?status=ngawur akan membuat halaman kosong tanpa
     * penjelasan, jadi nilainya dibatasi di sini dan nilai asing jatuh ke
     * bawaannya.
     *
     * @param  array<int, string>  $pilihan
     */
    private function terpilih(mixed $nilai, array $pilihan, string $bawaan): string
    {
        return in_array((string) $nilai, $pilihan, true) ? (string) $nilai : $bawaan;
    }
}
