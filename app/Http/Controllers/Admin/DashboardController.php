<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use App\Support\StatistikAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard admin: ringkasan platform, konten yang menunggu keputusan,
 * dan aktivitas terbaru.
 *
 * Semua angka diambil dari StatistikAdmin supaya halaman ini dan
 * halaman "Hasil & Statistik" tidak pernah menghitung hal yang sama
 * dengan cara berbeda.
 */
class DashboardController extends Controller
{
    /**
     * Berapa baris aktivitas yang ditampilkan di dashboard.
     */
    private const AKTIVITAS_TAMPIL = 6;

    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'pengguna' => $request->user(),
            'ringkasan' => StatistikAdmin::ringkasan(),
            'perluDitinjau' => StatistikAdmin::perluDitinjau(),
            'aktivitas' => $this->aktivitasTerbaru(),

            /*
             * Dua grafik paling bawah dashboard: pelajaran mana yang
             * paling sering dikerjakan, dan berapa kali orang masuk per
             * minggu. Keduanya dihitung di StatistikAdmin supaya halaman
             * ini dan halaman "Hasil & Statistik" memakai angka yang sama
             * persis.
             */
            'pelajaranDisukai' => StatistikAdmin::pelajaranTerpopuler(),
            'loginMingguan' => StatistikAdmin::loginMingguan(),
        ]);
    }

    /**
     * Materi dan quiz yang baru saja berubah status, untuk daftar
     * "Aktivitas Terbaru".
     *
     * Diurutkan dari yang paling baru diubah, bukan yang paling baru
     * dibuat: di halaman admin yang paling berguna adalah perubahan
     * keputusan, termasuk yang baru disetujui atau ditolak.
     *
     * Quiz ikut dicampur, bukan hanya materi. Daftar yang hanya berisi
     * satu jenis konten membuat admin mengira tidak ada yang terjadi di
     * jenis yang lain, jadi keduanya diambil dan digabung dengan
     * berdasarkan waktu yang sama.
     *
     * @return Collection<int, array{judul: string, detail: string, inisial: string, warna: string, warna_gelap: string, ikon: string, waktu: string}>
     */
    private function aktivitasTerbaru(): Collection
    {
        $materi = Materi::query()
            ->with('pembuat')
            ->latest('updated_at')
            ->limit(self::AKTIVITAS_TAMPIL)
            ->get()
            ->map(fn (Materi $materi): array => $this->barisAktivitas(
                $materi->pembuat,
                'mengajukan materi',
                $materi->nama,
                $materi->status === Materi::STATUS_PENDING ? 'jam' : 'buku',
                $materi->updated_at,
            ));

        $quiz = Quiz::query()
            ->with('pembuat')
            ->latest('updated_at')
            ->limit(self::AKTIVITAS_TAMPIL)
            ->get()
            ->map(fn (Quiz $quiz): array => $this->barisAktivitas(
                $quiz->pembuat,
                'membuat quiz',
                $quiz->judul,
                $quiz->status === Quiz::STATUS_PENDING ? 'jam' : 'soal',
                $quiz->updated_at,
            ));

        /*
         * Setelah digabung, urutannya dihitung ulang dari waktu yang
         * disimpan di setiap baris. Tanpa itu, semua materi akan selalu
         * mendahului semua quiz karena setiap kelompok sudah dipotong
         * lebih dulu sebelum digabung.
         */
        return $materi
            ->concat($quiz)
            ->sortByDesc('peringkat')
            ->take(self::AKTIVITAS_TAMPIL)
            ->values();
    }

    /**
     * Bentuk satu baris aktivitas.
     *
     * "peringkat" menyimpan waktu terakhirnya sebagai unix timestamp.
     * Field ini tidak pernah ditampilkan: fungsinya hanya jadi kunci
     * pengurutan setelah materi dan quiz digabung.
     *
     * @return array{judul: string, detail: string, inisial: string, warna: string, warna_gelap: string, ikon: string, waktu: string, peringkat: int}
     */
    private function barisAktivitas(
        ?User $pembuat,
        string $kalimat,
        string $isi,
        string $ikon,
        ?Carbon $waktu,
    ): array {
        $avatar = $pembuat?->warnaAvatar() ?? ['warna' => '#a78bfa', 'warna_gelap' => '#6c4de6'];
        $nama = $pembuat?->nama ?? 'Admin';

        return [
            'judul' => $nama.' '.$kalimat,
            'detail' => $isi,
            'inisial' => $pembuat?->inisial() ?? 'A',
            'warna' => $avatar['warna'],
            'warna_gelap' => $avatar['warna_gelap'],
            'ikon' => $ikon,
            'waktu' => $waktu?->diffForHumans() ?? 'baru saja',
            'peringkat' => $waktu?->getTimestamp() ?? 0,
        ];
    }
}
