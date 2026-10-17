<?php

namespace App\Providers;

use App\Models\MasukanSaran;
use App\Models\Materi;
use App\Models\Preferensi;
use App\Models\Quiz;
use App\Support\NotifikasiAdmin;
use App\Support\NotifikasiKonten;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Badge "Verifikasi" di sidebar admin: jumlah konten yang sedang
         * menunggu persetujuan, dan badge "Masukan & Saran" untuk kiriman
         * yang belum ditindaklanjuti. Keduanya dihitung sekali untuk semua
         * halaman admin, bukan per render: dengan database yang letaknya
         * terpisah, tiga COUNT di setiap pembukaan halaman langsung
         * terasa. Cache-nya pendek saja — segar 30 detik, lalu masih
         * disajikan sampai 180 detik sambil disegarkan di belakang layar —
         * jadi badge menyusul paling lama beberapa detik.
         */
        View::composer('layouts.admin', function (ViewContract $view): void {
            $pengguna = auth()->user();

            [$jumlahVerifikasi, $jumlahMasukan] = Cache::flexible('badge.admin', [30, 180], fn (): array => [
                Materi::query()->menunggu()->count() + Quiz::query()->menunggu()->count(),
                MasukanSaran::query()->baru()->count(),
            ]);

            $view->with('jumlahVerifikasi', $jumlahVerifikasi);
            $view->with('jumlahMasukan', $jumlahMasukan);

            /*
             * Tema dan aturan konfirmasi publish untuk area admin.
             *
             * Ditaruh di sini, bukan di controller, karena keduanya dipakai
             * layout dan hampir semua halaman admin memakainya, jadi tidak ada
             * satu pun controller yang perlu tahu tentang tema.
             *
             * Dua kolom ini dibaca dengan first() dan bukan lewat
             * Preferensi::ambil(). ambil() membuat baris kalau belum ada, dan
             * baris preferensi tidak boleh lahir hanya karena admin membuka
             * halaman Dashboard: kolomnya sudah punya nilai bawaan yang sama
             * dengan yang dilihat model, jadi belum ada pun hasil membacanya.
             * Dua kolom dibaca dalam satu query, bukan dua value() terpisah.
             *
             * Kalau barisnya memang belum ada, nilai bakunya dipakai: tema
             * terang dan konfirmasi publish menyala.
             */
            $preferensi = Preferensi::query()
                ->where('pengguna_id', $pengguna?->getKey())
                ->first(['tema', 'konfirmasi_publikasi']);

            $view->with([
                'temaAdmin' => $preferensi?->tema === Preferensi::TEMA_GELAP
                    ? Preferensi::TEMA_GELAP
                    : Preferensi::TEMA_TERANG,
                'konfirmasiPublikasi' => $preferensi?->konfirmasi_publikasi === null
                    ? '1'
                    : (int) $preferensi->konfirmasi_publikasi,
            ]);
        });

        /*
         * Lonceng notifikasi di top bar halaman user: daftar terbaru dan
         * jumlah yang belum dibaca.
         *
         * Diberi di sini, bukan dari controller, karena top bar dipakai
         * hampir semua halaman user dan tidak ada satu pun controller yang
         * boleh tahu tentang notifikasi. Compose-nya menempel pada kedua
         * kepala halaman user — top bar desktop dan header mobile — bukan
         * pada layout, supaya halaman yang menyembunyikan top bar (lihat
         * $sembunyiTopbar di layouts/app) tidak menjalankan query sama
         * sekali. Header mobile hanya me-render lonceng ketika baris
         * keduanya ada (tampilCari), jadi keputusannya sama: dua daftar
         * putih jadi satu.
         *
         * Hasilnya dicache singkat per pengguna (segar 30 detik, masih
         * disajikan sampai 120 detik sambil disegarkan): daftar dan angka
         * ini muncul di setiap halaman, dan panelnya dirender dua kali
         * (top bar desktop dan header mobile), jadi tanpa cache keduanya
         * berarti beberapa perjalanan lambat di setiap halaman. Begitu ada
         * notifikasi ditandai baca, model Notifikasi sendiri yang
         * menghapus cache-nya, jadi titik merahnya langsung hilang. Nama
         * host ikut masuk kunci cache karena isinya memuat tautan absolut
         * yang berbeda antara domain produksi dan domain lokal.
         */
        View::composer(['components.app.topbar', 'components.app.header-mobile'], function (ViewContract $view): void {
            $data = $view->getData();

            if ($view->name() === 'components.app.header-mobile' && ! ($data['tampilCari'] ?? true)) {
                $view->with(['notifikasi' => [], 'notifikasiBelumDibaca' => 0]);

                return;
            }

            $pengguna = $data['pengguna'] ?? auth()->user();

            if ($pengguna === null) {
                $view->with(['notifikasi' => [], 'notifikasiBelumDibaca' => 0]);

                return;
            }

            [$notifikasi, $notifikasiBelumDibaca] = Cache::flexible(
                'notif.user.'.$pengguna->getKey().'.'.request()->getHost(),
                [30, 120],
                fn (): array => [
                    NotifikasiKonten::daftar($pengguna),
                    NotifikasiKonten::belumDibaca($pengguna),
                ],
            );

            $view->with([
                'notifikasi' => $notifikasi,
                'notifikasiBelumDibaca' => $notifikasiBelumDibaca,
            ]);
        });

        /*
         * Lonceng notifikasi di topbar admin: daftar terbaru dan jumlah yang
         * belum dibaca.
         *
         * Diberi di sini, bukan dari controller, karena topbar dipakai hampir
         * semua halaman admin dan tidak ada satu pun controller yang boleh tahu
         * tentang notifikasi. Compose-nya menempel pada komponen topbar, bukan
         * pada layout, supaya halaman yang sengaja tidak memakai lonceng
         * (semua halaman /admin/pengaturan*, lihat $topbarRingkas di
         * layouts/admin) tidak menjalankan dua query ini sama sekali.
         *
         * Sama seperti lonceng pengguna: dicache singkat per admin, dan
         * cache-nya dihapus dari model Notifikasi begitu ada yang ditandai
         * baca.
         */
        View::composer('components.admin.topbar-kanan', function (ViewContract $view): void {
            $admin = $view->getData()['admin'] ?? auth()->user();

            if ($admin === null) {
                $view->with(['notifikasiAdmin' => collect(), 'notifBelum' => 0]);

                return;
            }

            [$notifikasiAdmin, $notifBelum] = Cache::flexible(
                'notif.admin.'.$admin->getKey().'.'.request()->getHost(),
                [30, 120],
                fn (): array => [
                    NotifikasiAdmin::daftar($admin),
                    NotifikasiAdmin::belumDibaca($admin),
                ],
            );

            $view->with([
                'notifikasiAdmin' => $notifikasiAdmin,
                'notifBelum' => $notifBelum,
            ]);
        });
    }
}
