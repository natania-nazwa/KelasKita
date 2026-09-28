<?php

namespace App\Models;

use App\Support\BabMateri;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Materi belajar (tabel tb_materi).
 *
 * Setiap materi selalu punya satu mata pelajaran (kategori) dari
 * tb_pelajaran, dan boleh dibuat oleh admin atau user.
 *
 * Materi tidak tayang begitu saja. Kolom status menentukan apakah isinya
 * boleh dibaca pengguna, dan status hanya bisa diubah lewat tiga cara:
 * pemilik mengajukan (Materi::ajukanPersetujuan()), admin menyetujui
 * (Materi::setujui()), atau admin menolak (Materi::tolak()).
 *
 *   draft     = baru dibuat, belum diajukan, tidak tampil di halaman Materi
 *   pending   = sudah diajukan, menunggu keputusan admin
 *   published = disetujui admin, tampil untuk semua pengguna
 *   rejected  = ditolak admin, lihat catatan_admin
 */
#[Fillable([
    'pelajaran_id',
    'dibuat_oleh',
    'nama',
    'slug',
    'deskripsi',
    'isi',
    'thumbnail',
    'audio',
    'tingkat_kesulitan',
    'status',
    'catatan_admin',
    'catatan_pengajuan',
    'dipublish_pada',
    'jumlah_ditolak',
])]
class Materi extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Berapa kali materi boleh ditolak sebelum tidak bisa lagi diajukan.
     *
     * Setelah mencapai batas ini materi masih boleh dibaca dan diubah
     * pemiliknya, tapi tidak bisa masuk daftar tunggu admin lagi.
     */
    public const BATAS_PENGAJUAN_ULANG = 2;

    /**
     * Nama tabel tidak mengikuti default Laravel ("materials").
     */
    protected $table = 'tb_materi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dipublish_pada' => 'datetime',
            'jumlah_ditolak' => 'integer',
        ];
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Materi yang sudah disetujui admin, jadi aman ditampilkan ke semua
     * pengguna. Inilah satu-satunya materi yang boleh muncul di halaman
     * Materi, di detail, dan di saran baca.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Materi yang sudah diajukan dan sedang menunggu keputusan admin.
     *
     * Dipakai halaman "Tinjau Materi" di area admin.
     */
    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Materi milik satu pengguna.
     *
     * Dipakai halaman "Karya Saya": filter ini yang membuat materi milik
     * orang lain tidak pernah ikut tampil di sana. Sengaja tanpa filter
     * status, supaya draft, materi yang ditolak, dan materi yang sedang
     * menunggu admin tetap bisa dikelola pemiliknya.
     */
    public function scopeMilik(Builder $query, ?int $idPembuat): Builder
    {
        return $query->where('dibuat_oleh', $idPembuat);
    }

    /**
     * Satu-satunya sumber kebenaran untuk "¿ini karya saya?".
     *
     * Dipakai halaman detail, form edit, dan hapus supaya satu tempat yang
     * menentukan apakah pengguna yang sedang login berhak mengelola materi ini.
     */
    public function dimilikiOleh(?int $idPengguna): bool
    {
        return $idPengguna !== null && (int) $this->dibuat_oleh === $idPengguna;
    }

    /**
     * Pemilik boleh mengajukan materi ini ke admin atau tidak.
     *
     * Tiga status bisa masuk daftar tunggu: draft (baru dibuat), materi yang
     * ditolak, dan materi yang sudah terbit lalu direvisi. Dua status terakhir
     * sama-sama berarti "perubahannya ikut perlu ditinjau", jadi keduanya
     * memakai satu jalan yang sama.
     *
     * Materi yang sedang menunggu tidak bisa diajukan lagi, dan materi yang
     * sudah ditolak BATAS_PENGAJUAN_ULANG kali tidak boleh masuk daftar
     * tunggu untuk ketiga kalinya.
     */
    public function bolehDiajukan(): bool
    {
        $siapDiajukan = in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_REJECTED,
        ], true);

        if (! $siapDiajukan) {
            return false;
        }

        /*
         * Batas pengajuan ulang hanya menahan materi yang belum pernah lolos
         * review. Materi yang sudah terbit tidak ikut dihitung: memperbaiki
         * materi yang sudah tayang bukan percobaan mengulang yang gagal, dan
         * memakai jatah yang sama akan membuat materi yang berhasil terbit
         * ikut terkunci setelah dua kali ditolak.
         */
        return $this->status !== self::STATUS_REJECTED || $this->sisaPengajuan() > 0;
    }

    /**
     * Sisa kesempatan mengajukan ulang, untuk ditampilkan ke pemilik.
     */
    public function sisaPengajuan(): int
    {
        return max(0, self::BATAS_PENGAJUAN_ULANG - (int) $this->jumlah_ditolak);
    }

    /**
     * Apakah pengajuan berikutnya wajib menyertakan catatan pendukung.
     *
     * Hanya berlaku untuk materi yang ditolak admin: di situ admin sudah
     * menuliskan alasan penolakannya, dan catatan pemiliklah yang
     * menjelaskan kenapa perbaikannya kini layak dipublikasikan. Materi
     * yang belum pernah dinilai tidak dimintai catatan sama sekali,
     * sehingga isian itu pun tidak ditampilkan di form.
     */
    public function perluCatatanPengajuan(): bool
    {
        return $this->bolehDiajukan() && $this->status === self::STATUS_REJECTED;
    }

    /**
     * Apakah materi ini pernah tayang, jadi revisinya perlu ditinjau lagi.
     */
    public function pernahTerbit(): bool
    {
        return $this->dipublish_pada !== null;
    }

    /**
     * Pemilik mengajukan materi ini ke admin.
     *
     * Catatan ditolak admin sengaja dikosongkan: begitu materi diajukan
     * ulang, catatan lama tidak lagi relevan karena isinya sudah diperbaiki.
     * Tanggal terbit juga dibersihkan supaya materi yang sedang menunggu
     * keputusan tidak terlihat punya tanggal terbit; tanggal baru hanya diisi
     * lagi ketika admin menyetujui.
     */
    public function ajukanPersetujuan(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PENDING,
            'catatan_admin' => null,
            'dipublish_pada' => null,
        ])->save();
    }

    /**
     * Admin menyetujui materi ini, lalu materi langsung tayang.
     */
    public function setujui(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PUBLISHED,
            'dipublish_pada' => now(),
            'catatan_admin' => null,
        ])->save();
    }

    /**
     * Admin menolak materi ini dan menuliskan alasannya.
     *
     * Penghitung ditolak bertambah satu supaya halaman "Karya Saya" bisa
     * memberi tahu sisa berapa kali materi ini masih boleh diajukan lagi.
     */
    public function tolak(string $alasan): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'catatan_admin' => $alasan,
            'jumlah_ditolak' => (int) $this->jumlah_ditolak + 1,
        ])->save();
    }

    /**
     * Label status untuk kartu di "Karya Saya", supaya pemilik tahu
     * materinya sudah tayang, sedang ditinjau, atau ditolak.
     */
    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_PUBLISHED => 'Dipublikasikan',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Draft',
        };
    }

    /**
     * Modifier warna untuk lencana status di kartu "Karya Saya".
     *
     * Dipakai sebagai kelas CSS karya-status--{nilai}, jadi nilainya harus
     * ikut kelas yang tersedia di resources/css/app.css.
     */
    public function warnaStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'menunggu',
            self::STATUS_PUBLISHED => 'terbit',
            self::STATUS_REJECTED => 'ditolak',
            default => 'draft',
        };
    }

    /**
     * Pencarian materi di halaman Materi.
     *
     * Mencari di nama, deskripsi, isi materi, dan nama mata pelajarannya.
     * Karakter % dan _ dari user di-escape supaya tidak jadi wildcard.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        /*
         * PostgreSQL membuat LIKE tidak peduli huruf besar-kecil. Tanpa
         * ILIKE, mengetik "katakana" tidak akan menemukan judul "Katakana".
         */
        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('nama', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhere('isi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola));
        });
    }

    /**
     * Filter kategori berdasarkan slug mata pelajaran.
     */
    public function scopeKategori(Builder $query, ?string $slug): Builder
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return $query;
        }

        return $query->whereHas(
            'pelajaran',
            fn (Builder $pelajaran) => $pelajaran->where('slug', $slug)
        );
    }

    /**
     * Cuplikan materi untuk ditampilkan di kartu.
     */
    public function ringkasan(int $jumlah = 110): string
    {
        return Str::limit(
            trim((string) ($this->ringkasan_teks ?? $this->deskripsi ?? $this->isi)),
            $jumlah
        );
    }

    public function getRingkasanTeksAttribute(): ?string
    {
        if (filled($this->deskripsi)) {
            return $this->deskripsi;
        }

        return filled($this->isi) ? Str::limit(strip_tags($this->isi), 160) : null;
    }

    /**
     * Perkiraan waktu baca dalam menit untuk ditampilkan di kartu.
     *
     * Dihitung dari jumlah kata isi materi dengan kecepatan baca sekitar
     * 200 kata per menit, minimal 1 menit supaya tidak pernah "0 menit".
     */
    public function waktuBaca(): int
    {
        $jumlahKata = str(strip_tags((string) $this->isi))
            ->squish()
            ->explode(' ')
            ->filter()
            ->count();

        return max(1, (int) ceil($jumlahKata / 200));
    }

    public function getWaktuBacaMenitAttribute(): int
    {
        return $this->waktuBaca();
    }

    /**
     * Perkiraan jumlah bab untuk ditampilkan di kartu.
     *
     * Bab tidak disimpan sebagai tabel sendiri: form "Tambah Materi" menyusun
     * seluruh bab menjadi satu teks polos di kolom "isi" (lihat
     * resources/js/materi-tambah.js). Karena itu penghitungannya menumpuk di
     * App\Support\BabMateri, pemecah yang sama yang dipakai form edit dan
     * halaman detail. Satu sumber berarti angka di kartu tidak pernah
     * berbeda dengan daftar bab yang benar-benar tampil.
     *
     * Materi tanpa penanda apa pun dihitung sebagai satu bab, jadi angkanya
     * tidak pernah nol.
     */
    public function jumlahBab(): int
    {
        return max(1, count(BabMateri::dariIsi($this->isi)));
    }
}
