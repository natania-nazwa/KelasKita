<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Membaca tabel "sessions" milik Laravel dan mengubahnya menjadi daftar
 * perangkat yang bisa ditampilkan di halaman "Sesi Login".
 *
 * Tabel sessions bukan tabel aplikasi: tidak ada modelnya, dibuat driver
 * database dari bawaan Laravel, dan isinya berupa baris yang berubah setiap
 * permintaan. Karena itu kelas ini memakai query builder langsung, bukan
 * Eloquent, dan tidak pernah mengubah apa pun selain menghapus baris pada
 * aksi "logout dari semua perangkat".
 *
 * Kolom yang dibaca semuanya memang ada di driver database:
 * user_id, ip_address, user_agent, dan last_activity.
 *
 * Kalau driver sesinya bukan database, kelas ini mengembalikan daftar kosong
 * dan halaman menampilkan catatan yang jujur, bukan baris perangkat tiruan.
 */
final class SesiAdmin
{
    /**
     * Waktu terakhir aktivitas pada tabel sessions disimpan sebagai UNIX
     * timestamp (detik), bukan sebagai kolom waktu. Angka ini dikembalikan
     * semua driver, jadi konversinya cukup di satu tempat.
     */
    private const KOLOM_WAKTU = 'last_activity';

    /**
     * Berapa banyak baris yang boleh ditampilkan sekaligus.
     *
     * Cukup untuk melihat perangkat yang berbeda, dan membatasi jumlah baris
     * supaya daftar yang panjang tidak membuat halaman berat.
     */
    public const MAKSIMAL = 20;

    /**
     * Apakah driver sesi saat ini benar-benar menyimpan baris di database.
     *
     * Dicek lewat konfigurasi, bukan dengan menebak. Driver lain (file,
     * cookie, redis) tidak punya tabel yang bisa dibaca, jadi method lain di
     * kelas ini tidak boleh dijalankan.
     */
    public static function tersimpanDiDatabase(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * Daftar perangkat milik satu pengguna, yang aktif dulu diurutkan paling atas.
     *
     * Setiap baris sudah dipetakan menjadi bentuk yang siap dipakai view:
     * nama peramban, sistem operasi, alamat IP, waktu terakhir aktif, dan
     * apakah baris itu adalah sesi yang sedang dipakai sekarang.
     *
     * "Peramban" dan "sistem operasi" dibaca dari user_agent. Kalau
     * user_agent kosong atau tidak dikenali, yang ditampilkan adalah keterangan
     * bahwa tidak diketahui, bukan tebakan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function daftar(User $pengguna, ?string $idSesiSekarang = null): Collection
    {
        if (! self::tersimpanDiDatabase()) {
            return collect();
        }

        return self::kueri()
            ->where('user_id', $pengguna->getKey())
            ->where(self::KOLOM_WAKTU, '>=', self::batasAktif())
            ->orderByDesc(self::KOLOM_WAKTU)
            ->limit(self::MAKSIMAL)
            ->get()
            ->map(fn (object $baris) => self::petakan($baris, $idSesiSekarang))
            ->values();
    }

    /**
     * Akhiri semua sesi milik satu pengguna kecuali sesi yang sedang dipakai.
     *
     * Menghapus baris di tabel sessions inilah yang benar-benar memutus sesi di
     * server: cookie di peramban lain masih ada, tapi tidak lagi cocok dengan
     * baris mana pun, sehingga ditolak pada permintaan berikutnya.
     *
     * Sesi sekarang dikecualikan supaya admin yang menekan tombol ini tidak ikut
     * terlempar keluar dari halamannya.
     *
     * @return int jumlah sesi yang diakhiri
     */
    public static function akhiri(User $pengguna, ?string $kecualiIdSesi = null): int
    {
        if (! self::tersimpanDiDatabase()) {
            return 0;
        }

        return self::kueri()
            ->where('user_id', $pengguna->getKey())
            ->when(
                filled($kecualiIdSesi),
                fn (QueryBuilder $query) => $query->where('id', '!=', $kecualiIdSesi)
            )
            ->delete();
    }

    /**
     * Hitung perangkat lain yang akan ikut terputus, untuk kalimat konfirmasi.
     *
     * Dipisah dari daftar() supaya halaman tidak perlu memfilter ulang baris
     * yang sudah diambil untuk ditampilkan.
     */
    public static function hitungLain(?User $pengguna, ?string $kecualiIdSesi = null): int
    {
        if ($pengguna === null || ! self::tersimpanDiDatabase()) {
            return 0;
        }

        return self::kueri()
            ->where('user_id', $pengguna->getKey())
            ->where(self::KOLOM_WAKTU, '>=', self::batasAktif())
            ->when(
                filled($kecualiIdSesi),
                fn (QueryBuilder $query) => $query->where('id', '!=', $kecualiIdSesi)
            )
            ->count();
    }

    /**
     * Batas bawah waktu aktivitas, dalam detik.
     *
     * Baris yang lebih tua dari ini dianggap sudah kedaluwarsa: Laravel sudah
     * membersihkannya sendiri sesuai "session.lifetime", jadi isinya tidak
     * boleh ditampilkan seolah-olah masih login.
     */
    private static function batasAktif(): int
    {
        return Carbon::now()->timestamp - (int) config('session.lifetime', 120) * 60;
    }

    /**
     * Query dasar ke tabel sesi.
     *
     * Nama tabel diambil dari config supaya ikut berubah kalau driver-nya
     * diganti.
     */
    private static function kueri(): QueryBuilder
    {
        return DB::connection()->table((string) config('session.table', 'sessions'));
    }

    /**
     * Ubah satu baris sessions menjadi bentuk yang dipakai view.
     *
     * @return array<string, mixed>
     */
    private static function petakan(object $baris, ?string $idSesiSekarang): array
    {
        $agent = $baris->user_agent ?? null;
        $terakhir = (int) ($baris->{self::KOLOM_WAKTU} ?: 0);
        $waktu = $terakhir > 0 ? Carbon::createFromTimestamp($terakhir) : null;

        /*
         * "Aktif sekarang" hanya diberikan ke sesi yang benar-benar sedang
         * dipakai. Baris lain yang kebetulan berdekatan waktunya tidak ditandai
         * aktif, supaya tanda ini tidak berarti "pernah aktif".
         */
        $ini = filled($idSesiSekarang) && $baris->id === $idSesiSekarang;

        return [
            'id' => $baris->id,
            'peramban' => self::peramban($agent),
            'sistem' => self::sistemOperasi($agent),
            'ip' => filled($baris->ip_address ?? null) ? (string) $baris->ip_address : null,
            'terakhir' => $waktu,
            'terakhir_label' => $waktu?->diffForHumans() ?? 'tidak diketahui',
            'ini' => $ini,
        ];
    }

    /**
     * Nama peramban dari user_agent.
     *
     * Diurut dari yang paling khas. Edge diperiksa sebelum Chrome dan sebelum
     * Safari karena user_agent Edge memuat "Chrome" dan "Safari" di dalamnya.
     */
    private static function peramban(?string $agent): string
    {
        if (blank($agent)) {
            return 'Peramban tidak dikenal';
        }

        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'YaBrowser') => 'Yandex',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            str_contains($agent, 'curl') => 'curl',
            str_contains($agent, 'PostmanRuntime') => 'Postman',
            default => 'Peramban tidak dikenal',
        };
    }

    /**
     * Nama sistem operasi dari user_agent.
     *
     * Sama seperti peramban(): kalau tidak dikenali, yang ditampilkan adalah
     * keterangan bahwa sistemnya tidak diketahui, bukan tebakan.
     */
    private static function sistemOperasi(?string $agent): string
    {
        if (blank($agent)) {
            return 'Sistem tidak diketahui';
        }

        return match (true) {
            // Harus diperiksa sebelum iPhone/iPad/iPod dan sebelum Mac,
            // karena user_agent iPadOS memuat "iPad", "iPhone", dan "Mac".
            str_contains($agent, 'iPad'), str_contains($agent, 'iPhone'), str_contains($agent, 'iPod') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Mac OS'), str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Sistem tidak diketahui',
        };
    }
}
