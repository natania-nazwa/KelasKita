<?php

namespace App\Providers;

use App\Models\Materi;
use App\Models\Preferensi;
use App\Models\Quiz;
use App\Support\NotifikasiAdmin;
use App\Support\NotifikasiKonten;
use Illuminate\Contracts\View\View as ViewContract;
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
         * menunggu persetujuan. Dihitung per render supaya angka selalu
         * segar, dan hanya untuk layout admin.
         */
        View::composer('layouts.admin', function (ViewContract $view): void {
            $pengguna = auth()->user();

            $view->with('jumlahVerifikasi',
                Materi::query()->menunggu()->count() + Quiz::query()->menunggu()->count()
            );

            /*
             * Tema dan aturan konfirmasi publish untuk area admin.
             *
             * Ditaruh di sini, bukan di controller, karena keduanya dipakai
             * layout dan hampir semua halaman admin memakainya, jadi tidak ada
             * satu pun controller yang perlu tahu tentang tema.
             *
             * Dua kolom ini dibaca dengan value() dan bukan lewat
             * Preferensi::ambil(). ambil() membuat baris kalau belum ada, dan
             * baris preferensi tidak boleh lahir hanya karena admin membuka
             * halaman Dashboard: kolomnya sudah punya nilai bawaan yang sama
             * dengan yang dilihat model, jadi belum ada pun hasil membacanya.
             *
             * Kalau barisnya memang belum ada, nilai bakunya dipakai: tema
             * terang dan konfirmasi publish menyala.
             */
            $tema = Preferensi::query()
                ->where('pengguna_id', $pengguna?->getKey())
                ->value('tema');

            $konfirmasi = Preferensi::query()
                ->where('pengguna_id', $pengguna?->getKey())
                ->value('konfirmasi_publikasi');

            $view->with([
                'temaAdmin' => $tema === Preferensi::TEMA_GELAP
                    ? Preferensi::TEMA_GELAP
                    : Preferensi::TEMA_TERANG,
                'konfirmasiPublikasi' => $konfirmasi === null ? '1' : (int) $konfirmasi,
            ]);
        });

        /*
         * Lonceng notifikasi di top bar halaman user: daftar terbaru dan
         * jumlah yang belum dibaca.
         *
         * Diberi di sini, bukan dari controller, karena top bar dipakai
         * hampir semua halaman user dan tidak ada satu pun controller yang
         * boleh tahu tentang notifikasi. Compose-nya hanya untuk top bar,
         * jadi halaman yang menyembunyikan top bar (lihat $sembunyiTopbar di
         * layouts/app) tidak menjalankan query sama sekali.
         */
        View::composer('components.app.topbar', function (ViewContract $view): void {
            $pengguna = $view->getData()['pengguna'] ?? auth()->user();

            $view->with([
                'notifikasi' => NotifikasiKonten::daftar($pengguna),
                'notifikasiBelumDibaca' => NotifikasiKonten::belumDibaca($pengguna),
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
         */
        View::composer('components.admin.topbar-kanan', function (ViewContract $view): void {
            $admin = $view->getData()['admin'] ?? auth()->user();

            $view->with([
                'notifikasiAdmin' => NotifikasiAdmin::daftar($admin),
                'notifBelum' => NotifikasiAdmin::belumDibaca($admin),
            ]);
        });
    }
}
