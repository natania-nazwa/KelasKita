<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIK
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing.index');
})->name('landing');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
|
| Sengaja TANPA middleware "guest".
|
| dulu route ini memakai "guest", jadi orang yang sudah login ikut
| dilempar ke "/" (landing page) ketika menekan tombol "Login/Daftar".
| Akibatnya tombol itu terlihat tidak bekerja, dan tidak pernah sampai
| ke halaman login.
|
| Sekarang /login dan /register selalu menampilkan formnya:
|   landing page -> /login -> (link "Daftar") -> /register
| lalu setelah berhasil baru redirect ke dashboard sesuai peran.
|
| Mengirim form login While sudah login diperbolehkan: sesi akan
| berpindah ke akun yang baru dipakai, lalu masuk ke dashboard-nya.
|
*/

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
|
| Hanya butuh login. Buka "/admin/..." di sini akan kena 403 karena
| route tersebut memakai middleware "admin".
|
*/

/*
| Prefix "user" mirrored the admin group: "/admin/dashboard".
| URL becomes /user/dashboard, /user/materi, /user/quiz, /user/profil.
| The route NAME stays "user.dashboard" etc. so every route() call in the
| views and controllers keeps working without modification.
*/

Route::middleware('auth')
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/dashboard', User\DashboardController::class)->name('dashboard');

        Route::get('/materi', User\MateriController::class)->name('materi');
        Route::get('/materi/{materi}', User\MateriDetailController::class)->name('materi.detail');
        Route::get('/quiz', User\QuizController::class)->name('quiz');
        Route::get('/profil', User\ProfilController::class)->name('profil');
    });

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
|
| Dijaga middleware "admin" (App\Http\Middleware\EnsureAdmin).
| Inilah proteksi sesungguhnya: meskipun menu disembunyikan di Blade,
| URL ini tetap akan ditolak 403 untuk selain admin.
|
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');
    });
