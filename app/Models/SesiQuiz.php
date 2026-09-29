<?php

namespace App\Models;

use App\Support\KodeQuiz;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesi quiz (tabel tb_sesi_quiz): satu sesi menjalankan quiz yang dibuka
 * oleh seorang host.
 *
 * Kenapa perlu tabel sendiri dan bukan memakai quiz_id langsung:
 * satu quiz bisa dijalankan berkali-kali (latihan minggu lalu, ulangan
 * minggu ini), dan tiap kali dijalankan punya daftar peserta serta statusnya
 * sendiri.
 *
 * HOST NYA SELALU PEMILIK QUIZ-nya, bukan orang yang pertama membuka sesi
 * (lihat App\Support\SesiKode). Untuk quiz mode kode, sesi bisa dibuat lebih
 * dulu oleh peserta yang mengetik kodenya; itu tidak membuat peserta itu jadi
 * host, cuma menyiapkan lobby tempat pemilik nanti membuka halaman ini.
 *
 * Kolom "kode" bukan lagi kunci pencarian. Kode yang diketik peserta adalah
 * kode akses quiz, dan sesi cukup memakai kode yang sama supaya angka yang
 * tampil di lobby sama dengan yang diketik peserta.
 *
 * Alur status:
 *   waiting  = masih di lobby. Peserta boleh masuk, belum boleh mengerjakan
 *              soal, host sudah bisa melihat daftar peserta.
 *   started  = host menekan "Mulai Quiz". Semua peserta otomatis masuk ke
 *              soal pertama.
 *   finished = quiz ditutup. Peserta diarahkan ke halaman hasil.
 */
#[Fillable(['quiz_id', 'host_id', 'kode', 'status', 'dimulai_pada', 'selesai_pada'])]
class SesiQuiz extends Model
{
    /** Masih di lobby: peserta boleh masuk, soal belum boleh dibuka. */
    public const STATUS_MENUNGGU = 'waiting';

    /** Sudah dimulai: peserta boleh mengerjakan soal. */
    public const STATUS_DIMULAI = 'started';

    /** Sudah ditutup: peserta diarahkan ke halaman hasil. */
    public const STATUS_SELESAI = 'finished';

    /**
     * Nama tabel tidak mengikuti default Laravel ("sesi_quizzes").
     */
    protected $table = 'tb_sesi_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Pembuat sesi. Hanya dia yang boleh memulai dan mengakhiri quiz.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(PesertaQuiz::class, 'sesi_id');
    }

    /**
     * Pengerjaan milik peserta sesi ini, dipakai halaman hasil.
     */
    public function pengerjaan(): HasMany
    {
        return $this->hasMany(PengerjaanQuiz::class, 'sesi_id');
    }

    /**
     * Sesi milik seorang host tertentu, dipakai untuk mencari sesi milik
     * dia sendiri yang masih terbuka.
     */
    public function scopeMilik(Builder $query, ?int $idHost): Builder
    {
        return $query->where('host_id', $idHost);
    }

    /**
     * Sesi yang belum ditutup host.
     */
    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_SELESAI);
    }

    /**
     * Bentuk baku kode sesi: huruf besar, tanpa spasi dan tanda hubung.
     *
     * Aturannya dipegang App\Support\KodeQuiz supaya kode sesi dan kode
     * akses quiz tidak bisa punya bentuk yang berbeda.
     */
    public static function normalisasiKode(?string $kode): string
    {
        return KodeQuiz::normalisasi($kode);
    }

    public function adalahHost(?User $pengguna): bool
    {
        return $pengguna !== null && $this->host_id === $pengguna->getKey();
    }

    public function sudahDimulai(): bool
    {
        return $this->status === self::STATUS_DIMULAI;
    }

    public function sudahSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    /**
     * Mulai quiz: waiting -> started.
     *
     * Hanya waiting yang bisa jadi started. Sesi yang sudah started atau
     * finished tidak berubah kalau metode ini dipanggil lagi, jadi menekan
     * dua kali tombol "Mulai Quiz" tidak membuat sesi ikut selesai.
     */
    public function mulai(): bool
    {
        if (! $this->masihMenunggu()) {
            return false;
        }

        $this->status = self::STATUS_DIMULAI;
        $this->dimulai_pada = now();
        $this->save();

        return true;
    }

    /**
     * Tutup quiz: sesi apa pun yang belum selesai -> finished.
     *
     * Berbeda dengan mulai(), metode ini tidak peduli sesi sedang
     * menunggu atau sudah berjalan, karena "Akhiri Quiz" harus berhasil
     * dari keduanya: host mungkin berubah pikiran sebelum memulainya.
     */
    public function tutup(): bool
    {
        if ($this->sudahSelesai()) {
            return false;
        }

        $this->status = self::STATUS_SELESAI;
        $this->selesai_pada = now();
        $this->save();

        return true;
    }

    /**
     * Sesi masih di lobby dan belum pernah dimulai.
     */
    public function masihMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    /**
     * Label status untuk ditampilkan di lobby dan di halaman hasil.
     */
    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'Menunggu host memulai',
            self::STATUS_DIMULAI => 'Sedang berjalan',
            self::STATUS_SELESAI => 'Sudah selesai',
            default => 'Menunggu host memulai',
        };
    }
}
