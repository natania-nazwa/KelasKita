<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu sesi pengerjaan quiz oleh seorang pengguna (tabel tb_pengerjaan_quiz).
 *
 * Tabel ini sudah ada sebelumnya, jadi fitur lobby memakainya apa adanya:
 * yang ditambahkan hanya kolom sesi_id, supaya pengerjaan tahu ia
 * bagian dari sesi mana yang sedang dijalankan. Jawaban tiap soal tetap
 * disimpan di tb_jawaban_quiz.
 */
#[Fillable([
    'sesi_id',
    'pengguna_id',
    'quiz_id',
    'jumlah_soal',
    'jumlah_dijawab',
    'jumlah_benar',
    'jumlah_salah',
    'nilai',
    'dimulai_pada',
    'selesai_pada',
])]
class PengerjaanQuiz extends Model
{
    /**
     * Nama tabel tidak mengikuti default Laravel ("pengerjaan_quizzes").
     */
    protected $table = 'tb_pengerjaan_quiz';

    /** Pengerjaan sudah ditutup: selesai_pada terisi dan nilainya lulus. */
    public const STATUS_SELESAI = 'selesai';

    /** Pengerjaan sudah ditutup tapi nilainya di bawah ambang lulus. */
    public const STATUS_GAGAL = 'gagal';

    /** Pengerjaan sudah dimulai tapi belum ditutup. */
    public const STATUS_PROSES = 'proses';

    /**
     * Nilai minimum (dari 100) supaya pengerjaan dihitung "Selesai".
     *
     * Aplikasi ini belum punya konsep passing score, jadi ambangnya
     * ditetapkan di sini, satu-satunya tempat. Mengubah angka di sini
     * otomatis mengubah filter tab dan label status, tidak ada angka
     * yang ditulis ulang di beberapa file.
     */
    public const NILAI_LULUS = 70;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_soal' => 'integer',
            'jumlah_dijawab' => 'integer',
            'jumlah_benar' => 'integer',
            'jumlah_salah' => 'integer',
            'nilai' => 'integer',
            'dimulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiQuiz::class, 'sesi_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanQuiz::class, 'pengerjaan_quiz_id');
    }

    public function sudahSelesai(): bool
    {
        return $this->selesai_pada !== null;
    }

    /**
     * Status pengerjaan yang dihitung dari data yang benar-benar tersimpan.
     *
     * Belum selesai = masih diproses, sudah selesai tapi di bawah
     * NILAI_LULUS = gagal, selebihnya lulus.
     */
    public function status(): string
    {
        if (! $this->sudahSelesai()) {
            return self::STATUS_PROSES;
        }

        return $this->nilai >= self::NILAI_LULUS
            ? self::STATUS_SELESAI
            : self::STATUS_GAGAL;
    }

    /**
     * Tulisan status untuk ditampilkan.
     */
    public function labelStatus(): string
    {
        return match ($this->status()) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_GAGAL => 'Gagal',
            default => 'Dalam Proses',
        };
    }

    /**
     * Soal yang tidak pernah dijawab peserta.
     *
     * jumlah_soal diambil saat pengerjaan dimulai, jadi angkanya tetap
     * benar walau soal quiz ditambah atau dikurangi setelah itu.
     */
    public function belumDijawab(): int
    {
        return max(0, (int) $this->jumlah_soal - (int) $this->jumlah_dijawab);
    }

    /**
     * Lama pengerjaan dalam detik, dihitung dari dua timestamp yang sudah
     * tersimpan. Pengerjaan yang belum ditutup dianggap 0 detik supaya
     * waktu yang belum selesai tidak ikut dihitung sebagai waktu belajar.
     */
    public function durasiDetik(): int
    {
        if ($this->dimulai_pada === null || $this->selesai_pada === null) {
            return 0;
        }

        return max(0, (int) round(abs($this->selesai_pada->getTimestamp() - $this->dimulai_pada->getTimestamp())));
    }

    /**
     * scopeMilik: batasi ke satu pengguna.
     *
     * Dipakai halaman "Hasil" supaya data milik orang lain tidak pernah
     * ikut terbaca, bukan hanya disembunyikan di tampilan.
     */
    public function scopeMilik(Builder $query, ?int $idPengguna): Builder
    {
        return $query->when(
            $idPengguna !== null,
            fn (Builder $q) => $q->where('pengguna_id', $idPengguna)
        );
    }

    /**
     * Pengerjaan yang sudah ditutup, apa pun nilainya.
     */
    public function scopeSelesai(Builder $query): Builder
    {
        return $query->whereNotNull('selesai_pada');
    }

    /**
     * Pengerjaan yang sudah dimulai tapi belum ditutup.
     */
    public function scopeProses(Builder $query): Builder
    {
        return $query->whereNull('selesai_pada');
    }

    /**
     * Pengerjaan yang sudah ditutup dan nilainya di bawah NILAI_LULUS.
     */
    public function scopeGagal(Builder $query): Builder
    {
        return $query->whereNotNull('selesai_pada')
            ->where('nilai', '<', self::NILAI_LULUS);
    }

    /**
     * Saring berdasarkan status yang dihitung PengerjaanQuiz::status().
     *
     * Nilai yang tidak dikenal dianggap "semua", supaya parameter URL yang
     * diketik manual tidak membuat halaman kosong tanpa penjelasan.
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_SELESAI => $query->selesai()->where('nilai', '>=', self::NILAI_LULUS),
            self::STATUS_GAGAL => $query->gagal(),
            self::STATUS_PROSES => $query->proses(),
            default => $query,
        };
    }

    /**
     * Batasi ke satu quiz, dipakai halaman riwayat per quiz yang dibuka dari
     * kartu "Quiz Terpopuler".
     */
    public function scopeQuiz(Builder $query, ?int $idQuiz): Builder
    {
        return $query->when(
            $idQuiz !== null,
            fn (Builder $q) => $q->where('quiz_id', $idQuiz)
        );
    }

    /**
     * Cari berdasarkan judul atau deskripsi quiz.
     *
     * Mengikuti Materi::scopeCari(): "ilike" di pgsql supaya pencarian
     * tidak membedakan huruf besar-kecil, "like" di driver lain.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // Escape wildcard SQL supaya "%" yang diketik pengguna dicari
        // sebagai teks biasa, bukan sebagai pola.
        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->whereHas('quiz', function (Builder $quiz) use ($operator, $pola) {
            $quiz->where('judul', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola);
        });
    }

    /**
     * Urutkan sesuai pilihan dropdown di halaman Hasil.
     */
    public function scopeUrut(Builder $query, ?string $urut): Builder
    {
        return match ($urut) {
            'terlama' => $query->orderBy('created_at')->orderBy('id'),
            'nilai-tinggi' => $query->orderByDesc('nilai')->orderByDesc('id'),
            'nilai-rendah' => $query->orderBy('nilai')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    /**
     * Soal yang jawabannya sudah dijawab peserta tetapi belum dinilai.
     *
     * Hanya soal paragraf yang masuk sini: isinya bebas dan tidak bisa
     * dibandingkan dengan kunci, jadi harus dibaca orang. Sifatnya
     * dihitung dari soal-soal yang ada, bukan disimpan, supaya jawaban
     * lama yang belum pernah dinilai pun ikut terhitung begitu halaman
     * hasil dibuka.
     */
    public function jumlahMenungguNilai(): int
    {
        return $this->jawaban()
            ->with('soal')
            ->get()
            ->filter(fn (JawabanQuiz $jawaban) => $jawaban->soal?->perluNilaiManual() === true)
            ->count();
    }

    /**
     * Nilai dalam persen 0-100, dihitung ulang dari jawaban yang tersimpan.
     *
     * Dihitung ulang (bukan ditambahkan) supaya jawaban yang diubah peserta
     * tidak membuat angka dobel.
     *
     * Jawaban yang belum dinilai (soal paragraf) ikut dihitung sebagai
     * "sudah dijawab" supaya peserta tidak melihat soal yang sudah
     * dikerjakannya seolah belum disentuh, tapi tidak ikut masuk benar
     * maupun salah.
     * Pembagi tetap jumlah_soal supaya skalanya tidak berubah-ubah: nilai
     * yang belum lengkap terlihat sebagai nilai yang belum maksimal, bukan
     * sebagai 100 persen.
     */
    public function hitungUlang(): void
    {
        $jawaban = $this->jawaban()->with('soal')->get();

        $dinilai = $jawaban->reject(
            fn (JawabanQuiz $item) => $item->soal?->perluNilaiManual() === true
        );

        $benar = $dinilai->where('benar', true)->count();

        $this->jumlah_dijawab = $jawaban->count();
        $this->jumlah_benar = $benar;
        $this->jumlah_salah = $dinilai->count() - $benar;
        $this->nilai = $this->jumlah_soal > 0
            ? (int) round($benar / $this->jumlah_soal * 100)
            : 0;

        $this->save();
    }
}
