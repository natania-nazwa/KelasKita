<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi milik satu pengguna (tabel tb_notifikasi).
 *
 * Satu tabel ini dipakai dua lonceng sekaligus dan keduanya menulis ke
 * tempat yang sama:
 *
 *   - lonceng admin, lewat App\Support\NotifikasiAdmin: karya sendiri yang
 *     terbit atau disimpan sebagai draft, hasil kuis baru, dan karya pengguna
 *     yang menunggu diperiksa;
 *   - lonceng pengguna, lewat App\Support\NotifikasiKonten: konten baru yang
 *     ditayangkan, keputusan admin atas karyanya sendiri, dan hasil quiz
 *     yang baru selesai dikerjakan.
 *
 * Yang disimpan di sini bukan salinan isi konten, tapi koordinatnya: jenis
 * notifikasi, judul, pesan, dan tautan ke konten asalnya. Isi materi atau
 * quiz yang berubah karena diedit admin tidak perlu ikut diperbarui di sini,
 * karena yang dibaca pembaca tetap kontennya, bukan salinannya.
 */
#[Fillable([
    'pengguna_id',
    'jenis',
    'judul',
    'pesan',
    'konten_id',
    'konten_tipe',
    'dibaca_pada',
])]
class Notifikasi extends Model
{
    /** Materi baru diterbitkan admin. */
    public const JENIS_MATERI_BARU = 'materi_baru';

    /** Quiz baru diterbitkan admin. */
    public const JENIS_QUIZ_BARU = 'quiz_baru';

    /*
     * Empat jenis berikut hanya masuk ke lonceng admin, bukan ke milik
     * pengguna. Semuanya ditulis oleh App\Support\NotifikasiAdmin, dan setiap
     * jenisnya punya satu saklar preferensi yang mengendalikannya, jadi
     * mematikan saklarnya di Pengaturan benar-benar menghentikan notifikasi
     * jenis itu.
     */

    /** Admin menerbitkan karyanya sendiri. */
    public const JENIS_KONTEN_TERBIT = 'konten_terbit';

    /** Admin menyimpan karyanya sebagai draft. */
    public const JENIS_KONTEN_DRAFT = 'konten_draft';

    /** Hasil kuis baru dari seorang pengguna. */
    public const JENIS_HASIL_KUIS = 'hasil_kuis';

    /** Karya pengguna yang menunggu diperiksa admin. */
    public const JENIS_KONTEN_MENUNGGU = 'konten_menunggu';

    /*
     * Tiga jenis berikut dikirim ke lonceng pemilik karyanya sendiri, bukan
     * ke lonceng admin. Semuanya ditulis oleh App\Support\NotifikasiKonten.
     */

    /** Admin menyetujui materi atau quiz milik pengguna ini. */
    public const JENIS_KARYA_DISETUJUI = 'karya_disetujui';

    /** Admin menolak karya milik pengguna ini, beserta alasannya. */
    public const JENIS_KARYA_DITOLAK = 'karya_ditolak';

    /** Pengguna selesai mengerjakan quiz, hasilnya sudah tersimpan. */
    public const JENIS_QUIZ_SELESAI = 'quiz_selesai';

    /** Sembilan jenis untuk dropdown dan validasi. */
    public const JENIS_TERSEDIA = [
        self::JENIS_MATERI_BARU,
        self::JENIS_QUIZ_BARU,
        self::JENIS_KONTEN_TERBIT,
        self::JENIS_KONTEN_DRAFT,
        self::JENIS_HASIL_KUIS,
        self::JENIS_KONTEN_MENUNGGU,
        self::JENIS_KARYA_DISETUJUI,
        self::JENIS_KARYA_DITOLAK,
        self::JENIS_QUIZ_SELESAI,
    ];

    /**
     * Ikon yang dipakai lonceng untuk setiap jenis notifikasi.
     *
     * Dipetakan di model, bukan di view, supaya view tidak harus tahu daftar
     * jenisnya. Dipakai kedua lonceng: milik pengguna dan milik admin, supaya
     * kabar yang sama memakai lambang yang sama.
     *
     * @return array<string, string>
     */
    public const IKON = [
        self::JENIS_MATERI_BARU => 'buku',
        self::JENIS_QUIZ_BARU => 'centang',
        self::JENIS_KONTEN_TERBIT => 'centang',
        self::JENIS_KONTEN_DRAFT => 'file-teks',
        self::JENIS_HASIL_KUIS => 'piala',
        self::JENIS_KONTEN_MENUNGGU => 'jam',
        self::JENIS_KARYA_DISETUJUI => 'centang',
        self::JENIS_KARYA_DITOLAK => 'silang',
        self::JENIS_QUIZ_SELESAI => 'piala',
    ];

    /**
     * Nama ikon untuk jenis ini, dengan nilai bawaan yang aman.
     */
    public static function ikon(string $jenis): string
    {
        return self::IKON[$jenis] ?? 'lonceng';
    }

    /** Nilai kolom konten_tipe untuk materi. */
    public const KONTEN_MATERI = 'materi';

    /** Nilai kolom konten_tipe untuk quiz. */
    public const KONTEN_QUIZ = 'quiz';

    /**
     * Nilai kolom konten_tipe untuk satu pengerjaan quiz.
     *
     * Bedanya dengan KONTEN_QUIZ penting: pada KONTEN_QUIZ, konten_id menunjuk
     * ke quiz-nya (halaman "mulai"), sedangkan di sini konten_id menunjuk ke
     * tb_pengerjaan_quiz (halaman rincian jawaban milik pengguna itu sendiri).
     * Satu jenis notifikasi saja yang memakai nilai ini, yaitu kabar bahwa
     * hasil kuis sudah tersimpan.
     */
    public const KONTEN_PENGERJAAN = 'pengerjaan';

    /**
     * Nama tabel tidak mengikuti default Laravel ("notifications").
     *
     * Sengaja tabel sendiri, bukan tabel "notifications" bawaan Laravel:
     * nama kolomnya dibuat eksplisit (judul, pesan, konten) supaya lonceng
     * di topbar bisa merakit tampilan dari kolom biasa, bukan dari isi JSON
     * milik kelas notifikasi yang tidak ada di aplikasi ini.
     */
    protected $table = 'tb_notifikasi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    /**
     * Hanya notifikasi yang belum dibaca, untuk titik di lonceng.
     */
    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_pada');
    }

    /**
     * Notifikasi sudah dibaca atau belum.
     */
    public function sudahDibaca(): bool
    {
        return $this->dibaca_pada !== null;
    }

    /**
     * Tandai notifikasi ini sudah dibaca.
     *
     * Sudah dibaca tidak diubah lagi: notifikasi yang dibuka dua kali tidak
     * boleh memindahkan tanggal bacanya, karena urutan di daftar ditentukan
     * oleh kapan notifikasi itu dibuat, bukan kapan terakhir dibuka.
     */
    public function tandaiDibaca(): void
    {
        if ($this->dibaca_pada !== null) {
            return;
        }

        $this->forceFill(['dibaca_pada' => now()])->save();
    }

    /**
     * Model konten yang jadi sumber pesan, atau null kalau kontennya sudah
     * dihapus.
     *
     * Dipakai supaya lonceng notifikasi bisa menulis tautan yang benar tanpa
     * menebak nama route-nya.
     */
    public function konten(): ?Model
    {
        return match ($this->konten_tipe) {
            self::KONTEN_MATERI => Materi::query()->find($this->konten_id),
            self::KONTEN_QUIZ => Quiz::query()->find($this->konten_id),
            self::KONTEN_PENGERJAAN => PengerjaanQuiz::query()
                ->whereKey($this->konten_id)
                ->where('pengguna_id', $this->pengguna_id)
                ->first(),
            default => null,
        };
    }

    /**
     * Halaman tujuan notifikasi ini untuk admin, atau null kalau barisnya bukan
     * notifikasi admin.
     *
     * Empat jenis notifikasi admin diselesaikan di sini supaya panel lonceng
     * tidak perlu tahu jalan mana yang benar untuk tiap jenis:
     *
     *   - karya admin sendiri -> form edit di Konten Pembelajaran, karena
     *     notifikasi itu kabar tentang mengelola konten, bukan membacanya;
     *   - hasil kuis baru      -> dashboard admin. Dulu halaman "Hasil &
     *     Statistik", tapi halaman itu sudah tidak ada di aplikasi sekarang;
     *   - menunggu ditinjau    -> Verifikasi, tempat keputusan diambil.
     *
     * Kalau kontennya sudah dihapus, tautannya hilang dan barisnya tetap
     * ditampilkan sebagai teks biasa. Pesannya masih benar sebagai kabar, dan
     * tidak ada lagi halaman yang bisa dituju.
     */
    public function tautanAdmin(): ?string
    {
        if ($this->jenis === self::JENIS_HASIL_KUIS) {
            return route('admin.dashboard');
        }

        if ($this->jenis === self::JENIS_KONTEN_MENUNGGU) {
            return route('admin.verifikasi');
        }

        if (! in_array($this->jenis, [self::JENIS_KONTEN_TERBIT, self::JENIS_KONTEN_DRAFT], true)) {
            return null;
        }

        // Satu kali saja: setiap pemanggilan konten() membuka query baru.
        $konten = $this->konten();

        return match ($this->konten_tipe) {
            self::KONTEN_MATERI => $konten?->slug
                ? route('admin.konten.materi.edit', $konten->slug)
                : null,
            self::KONTEN_QUIZ => $konten
                ? route('admin.konten.quiz.edit', $konten->getKey())
                : null,
            default => null,
        };
    }
}
