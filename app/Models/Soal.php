<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Satu soal milik sebuah quiz (tabel tb_soal).
 *
 * Setiap soal punya tipenya sendiri, jadi satu quiz bisa campur soal
 * pilihan ganda, soal pilihan banyak, dan soal essay. Tipenya disimpan di
 * kolom tipe memakai nilai internal english snake_case, bukan label bahasa
 * Indonesia, supaya tidak ikut berubah kalau labelnya diganti.
 *
 * Pilihan jawaban disimpan sebagai baris di tb_soal_pilihan (lihat
 * SoalPilihan). Kolom pilihan_a sampai pilihan_f di tabel ini sengaja
 * masih ada dan tetap diisi, karena kolomnya NOT NULL dan sudah dipakai
 * data quiz lama; semua pembaca baru memakai pilihanSoal().
 *
 * Dua hal yang tidak muat di skema kolom lama, dan sengaja dipisah:
 * jawaban bertipe teks (jawaban_teks) dipakai short_answer dan paragraph,
 * sedangkan jawaban_benar varchar(1) hanya bisa menyimpan satu huruf dan
 * karena itu tidak bisa dipakai tipe multiple_select.
 */
#[Fillable([
    'quiz_id',
    'pertanyaan',
    'tipe',
    'pilihan_a',
    'pilihan_b',
    'pilihan_c',
    'pilihan_d',
    'pilihan_e',
    'pilihan_f',
    'jawaban_benar',
    'jawaban_teks',
    'tococok_persis',
    'pembahasan',
    'urutan',
    'tingkat_kesulitan',
    'aktif',
])]
class Soal extends Model
{
    /*
     * Nilai internal tipe soal. Nama file dan label tampilannya sengaja
     * dipisah supaya kode di seluruh aplikasi bebas dari bahasa Indonesia,
     * sementara yang dilihat pengguna tetap berbahasa Indonesia.
     */
    public const TIPE_PILIHAN_GANDA = 'multiple_choice';

    public const TIPE_PILIHAN_BANYAK = 'multiple_select';

    public const TIPE_DROPDOWN = 'dropdown';

    public const TIPE_JAWABAN_SINGKAT = 'short_answer';

    public const TIPE_PARAGRAF = 'paragraph';

    /**
     * Benar / Salah: dua pilihan tetap "Benar" dan "Salah", tepat satu
     * jawaban benar. Disimpan seperti pilihan ganda (baris A dan B di
     * tb_soal_pilihan) supaya penilaian dan halaman mengerjakan tidak
     * butuh jalur khusus.
     */
    public const TIPE_BENAR_SALAH = 'true_false';

    /** Dua teks pilihan bawaan tipe Benar / Salah, sesuai urutan huruf. */
    public const PILIHAN_BENAR_SALAH = ['Benar', 'Salah'];

    /** Pilihan ganda: satu jawaban benar. */
    public const TIPE_GANDA = self::TIPE_PILIHAN_GANDA;

    /** Pilihan banyak: lebih dari satu jawaban benar boleh dipilih. */
    public const TIPE_BANYAK = self::TIPE_PILIHAN_BANYAK;

    /** Batas pilihan jawaban per soal, dipakai builder dan validasi. */
    public const MAKSIMAL_PILIHAN = 10;

    /** Pilihan jawaban minimal per soal. */
    public const MINIMAL_PILIHAN = 2;

    /**
     * Huruf label yang boleh dipakai untuk pilihan jawaban.
     *
     * Jumlahnya sama dengan MAKSIMAL_PILIHAN, jadi menambah batas pilihan
     * cukup menambah huruf di sini.
     *
     * @return array<int, string>
     */
    public static function hurufTersedia(): array
    {
        $huruf = range('A', 'Z');

        return array_slice($huruf, 0, self::MAKSIMAL_PILIHAN);
    }

    /**
     * Nama tabel tidak mengikuti default Laravel ("soals").
     */
    protected $table = 'tb_soal';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
            'tococok_persis' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Pilihan jawaban milik soal ini, urut dari atas.
     *
     * Diurutkan berdasarkan huruf, bukan urutan, supaya label A, B, C
     * selalu menempel pada teks yang sama walau barisnya tersusun ulang.
     */
    public function pilihanSoal(): HasMany
    {
        return $this->hasMany(SoalPilihan::class, 'soal_id')->orderBy('huruf');
    }

    /**
     * Soal yang aman ditampilkan ke user.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Urutan tampilan soal di halaman mengerjakan quiz.
     */
    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('id');
    }

    /*
     * ============================================================
     * TIPE
     * ============================================================
     */

    /**
     * Seluruh tipe yang didukung, untuk dropdown builder dan validasi.
     *
     * @return array<int, string>
     */
    public static function tipeTersedia(): array
    {
        return [
            self::TIPE_PILIHAN_GANDA,
            self::TIPE_PILIHAN_BANYAK,
            self::TIPE_BENAR_SALAH,
            self::TIPE_DROPDOWN,
            self::TIPE_JAWABAN_SINGKAT,
            self::TIPE_PARAGRAF,
        ];
    }

    /**
     * Label bahasa Indonesia untuk setiap tipe.
     *
     * Dipakai builder dan halaman hasil, supaya nama tipe tidak ditulis
     * ulang di beberapa tempat.
     *
     * @return array<string, string>
     */
    public static function labelTipe(string $tipe): string
    {
        return match ($tipe) {
            self::TIPE_PILIHAN_BANYAK => 'Checkbox / Pilihan Banyak',
            self::TIPE_BENAR_SALAH => 'Benar / Salah',
            self::TIPE_DROPDOWN => 'Dropdown',
            self::TIPE_JAWABAN_SINGKAT => 'Jawaban Singkat',
            self::TIPE_PARAGRAF => 'Paragraf',
            default => 'Pilihan Ganda',
        };
    }

    /**
     * Tipe soal ini, dengan nilai cadangannya untuk soal lama.
     *
     * Soal yang dibuat sebelum kolom tipe ada tidak punya isian di sini, dan
     * semua isiannya jelas bermakna sebagai pilihan ganda.
     *
     * @param  mixed  $tipe
     */
    public static function normalkanTipe($tipe): string
    {
        return in_array($tipe, self::tipeTersedia(), true)
            ? $tipe
            : self::TIPE_PILIHAN_GANDA;
    }

    /**
     * Tipe soal milik instans ini. Soal yang belum punya baris di
     * tb_soal_pilihan dianggap pilihan ganda, sama seperti aslinya.
     */
    public function tipe(): string
    {
        return self::normalkanTipe($this->tipe);
    }

    /**
     * Apakah tipe ini memakai daftar pilihan jawaban (dengan atau tanpa
     * huruf A, B, C).
     *
     * Empat tipe pertama memakai daftar — termasuk Benar / Salah yang
     * daftarnya selalu "Benar" dan "Salah" — sedangkan dua tipe teks
     * terakhir tidak punya pilihan sama sekali.
     */
    public function tipePakaiPilihan(): bool
    {
        return in_array(
            $this->tipe(),
            [self::TIPE_PILIHAN_GANDA, self::TIPE_PILIHAN_BANYAK, self::TIPE_BENAR_SALAH, self::TIPE_DROPDOWN],
            true,
        );
    }

    /**
     * Apakah tipe ini boleh punya lebih dari satu jawaban benar.
     */
    public function tipeBanyakBenar(): bool
    {
        return $this->tipe() === self::TIPE_PILIHAN_BANYAK;
    }

    /**
     * Apakah jawaban peserta berupa teks bebas, bukan memilih pilihan.
     */
    public function tipeTeks(): bool
    {
        return in_array(
            $this->tipe(),
            [self::TIPE_JAWABAN_SINGKAT, self::TIPE_PARAGRAF],
            true,
        );
    }

    /**
     * Apakah soal ini harus dinilai orang, bukan otomatis.
     *
     * Hanya soal paragraf: isinya bebas dan tidak ada satu jawaban benar
     * yang tunggal, jadi tidak bisa dinilai dengan dibandingkan teks. Jawaban
     * peserta tetap disimpan utuh supaya guru bisa menilainya nanti, tapi
     * selama belum dinilai jawaban itu tidak boleh masuk hitungan benar
     * maupun salah — kalau tidak, peserta yang menjawab dengan jujur malah
     * langsung dapat nilai nol.
     */
    public function perluNilaiManual(): bool
    {
        return $this->tipe() === self::TIPE_PARAGRAF;
    }

    /*
     * ============================================================
     * PILIHAN JAWABAN
     * ============================================================
     */

    /**
     * Daftar pilihan dalam bentuk ['A' => teks, 'B' => teks, ...].
     *
     * Sumbernya adalah baris tb_soal_pilihan. Kalau baris itu belum ada —
     * misalnya quiz lama yang dibuat sebelum tabelnya ada — metode ini
     * membaca kolom pilihan_a sampai pilihan_f, jadi tidak ada soal lama
     * yang ikut hilang.
     *
     * Pilihan kosong tidak ikut dikembalikan. Semua halaman yang
     * menampilkan soal memetakan lewat metode ini, jadi menyaring di sini
     * cukup untuk semuanya.
     *
     * @return array<string, string>
     */
    public function pilihan(): array
    {
        $hasil = [];

        foreach ($this->daftarPilihan() as $pilihan) {
            $teks = trim((string) $pilihan->teks);

            if ($teks !== '') {
                $hasil[$pilihan->huruf] = $teks;
            }
        }

        return $hasil;
    }

    /**
     * Baris tb_soal_pilihan milik soal ini, atau daftar tiruan dari kolom
     * lama kalau tabelnya belum berisi apa pun untuk soal ini.
     *
     * Metode ini memuat relasinya lebih dulu kalau belum dimuat, supaya
     * pemanggil cukup memanggil satu method tanpa perlu tahu soal ini
     * punya baris di tb_soal_pilihan atau tidak.
     *
     * @return Collection<int, SoalPilihan>
     */
    public function daftarPilihan()
    {
        if (! $this->relationLoaded('pilihanSoal')) {
            $this->load('pilihanSoal');
        }

        if ($this->pilihanSoal->isNotEmpty()) {
            return $this->pilihanSoal;
        }

        return $this->pilihanKolomLama();
    }

    /**
     * Pilihannya sebagai baris SoalPilihan tiruan, dibaca dari kolom
     * pilihan_a sampai pilihan_f. Dipakai supaya soal lama bisa lewat
     * jalur kode yang sama dengan soal baru.
     *
     * @return Collection<int, SoalPilihan>
     */
    public function pilihanKolomLama()
    {
        $hasil = collect();

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $huruf) {
            $teks = trim((string) $this->{self::kolomPilihan($huruf)});

            if ($teks === '') {
                continue;
            }

            $hasil->push(new SoalPilihan([
                'huruf' => $huruf,
                'teks' => $teks,
                'benar' => $this->jawaban_benar === $huruf,
            ]));
        }

        return $hasil;
    }

    /**
     * Huruf jawaban yang benar, selalu berupa larik supaya tipe
     * multiple_select dan tipe lain bisa lewat jalur yang sama.
     *
     * @return array<int, string>
     */
    public function hurufBenar(): array
    {
        $dariPilihan = $this->daftarPilihan()
            ->filter(fn (SoalPilihan $pilihan) => (bool) $pilihan->benar)
            ->pluck('huruf')
            ->all();

        if ($dariPilihan !== []) {
            return array_values($dariPilihan);
        }

        // Kolom jawaban_benar hanya menyimpan satu huruf, jadi untuk soal
        // bertipe pilihan banyak larik ini hanya bisa memuat satu dari
        // beberapa jawaban benarnya. Soal baru tidak pernah masuk sini
        // karena jawabannya sudah tersimpan di tb_soal_pilihan.
        return $this->jawaban_benar === null || $this->jawaban_benar === ''
            ? []
            : [$this->jawaban_benar];
    }

    /**
     * Jawaban benar dalam bentuk teks, untuk soal bertipe teks.
     */
    public function kunciTeks(): string
    {
        return (string) $this->jawaban_teks;
    }

    /**
     * Nama kolom tb_soal yang menyimpan pilihan jawaban tertentu, mis.
     * kolomPilihan('A') menghasilkan "pilihan_a".
     *
     * Hanya dipakai untuk menulis kolom cadangan yang NOT NULL; pembaca
     * baru memakai pilihan().
     */
    public static function kolomPilihan(string $huruf): string
    {
        return 'pilihan_'.strtolower($huruf);
    }
}
