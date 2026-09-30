<?php

namespace App\Providers;

use App\Models\Materi;
use App\Models\Quiz;
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
            $view->with(
                'jumlahVerifikasi',
                Materi::query()->menunggu()->count() + Quiz::query()->menunggu()->count()
            );
        });
    }
}
