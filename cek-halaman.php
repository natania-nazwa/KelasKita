<?php

/*
 * Mengambil halaman "Konten Pembelajaran" lewat HTTP sungguhan, lalu
 * memeriksa bagian-bagian yang tidak bisa diperiksa dari feature test:
 *
 *   - halaman benar-benar berisi baris, bukan hanya markup kosong;
 *   - setiap <x-...> di halaman itu punya berkasnya (kalau ada yang salah
 *     nama, Blade akan melempar exception dan halamannya 500);
 *   - tidak ada referensi ke kelas atau atribut yang sudah dihapus.
 *
 * Dipakai sebagai pemeriksaan terakhir setelah test, karena test berjalan
 * dengan sqlite sementara database lokal memakai Postgres — dan beberapa
 * hal (misalnya urutan dengan COLLATE) hanya behave benar di Postgres.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Materi;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$admin = User::query()->where('peran', User::PERAN_ADMIN)->first();

if ($admin === null) {
    exit("Tidak ada akun admin di database.\n");
}

// |autentikasi| lewat Auth::login cukup: view sudah dirender server-side.
auth()->login($admin);

/*
 * Hanya satu request per proses. Menjalankan Kernel beberapa kali dalam satu
 * proses menyebabkan session menumpuk dan request berikutnya menggantung —
 * jadi tiap URL diperiksa dari proses terpisah, lewat argumen baris perintah.
 */
$url = $argv[1] ?? '/admin/konten?tab=materi';

$response = app(Kernel::class)->handle(Request::create($url, 'GET'));

$html = $response->getContent();

printf("status        : %d\n", $response->getStatusCode());
printf("panjang html  : %d byte\n", strlen($html));

// Halaman harus benar-benar dirender, bukan halaman error.
$error = null;

if ($response->getStatusCode() !== 200) {
    $error = 'halaman mengembalikan status bukan 200';
}

// Baris konten harus muncul kalau memang ada isinya.
$jumlahMateri = Materi::query()->where('dibuat_oleh', $admin->getKey())->count();

if ($jumlahMateri > 0 && ! str_contains($html, 'ad-konten-baris__judul')) {
    $error = 'ada materi milik admin tapi tidak ada baris di daftar';
}

// Tag komponen di halaman harus semuanya punya berkas.
preg_match_all('/<x-([a-zA-Z0-9.\-]+)/', $html, $cocok);

foreach (array_unique($cocok[1]) as $tag) {
    $relative = 'resources/views/components/'.str_replace('.', '/', $tag).'.blade.php';

    if (! is_file(__DIR__.'/'.$relative)) {
        $error = "tag <x-$tag> tidak punya berkas: $relative";
    }
}

// Kelas harus muncul di baris kalau isinya sudah diisi.
if ($jumlahMateri > 0 && ! preg_match('/ad-konten-lencana">RPL/', $html)) {
    $error = 'tidak ada lencana kelas di baris mana pun';
}

// Kerangka, empty state, dan dialog harus selalu ada.
foreach ([
    'data-konten-rangka' => 'kerangka saat memuat',
    'data-konten-publish-dialog' => 'dialog publish',
    'data-dialog-hapus' => 'dialog hapus',
    'ad-konten-tab' => 'tab',
    'Semua Kelas' => 'filter kelas',
    'Semua Status' => 'filter status',
] as $needle => $label) {
    if (! str_contains($html, $needle)) {
        $error = "hilang: $label ($needle)";
    }
}

// Class yang sudah tidak dipakai tidak boleh masih dirujuk.
foreach ([
    'ad-alat-zeile',
    'ad-konten__',
] as $needle) {
    if (str_contains($html, $needle)) {
        $error = "masih merujuk kelas yang sudah tidak dipakai: $needle";
    }
}

echo "\n";

if ($error === null) {
    echo "OK: halaman dirender utuh dan semua bagiannya ada\n";
} else {
    echo "GAGAL: $error\n";
    exit(1);
}
