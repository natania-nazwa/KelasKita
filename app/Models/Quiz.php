<?php

namespace App\Models;

use App\Support\KodeQuiz;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz (tabel tb_quiz).
 *
 * Setiap quiz selalu punya satu mata pelajaran (kategori) dari tb_pelajaran
 * dan satu pembuat dari tb_pengguna. Soal-soalnya berada di tb_soal.
 *
 * Quiz punya DUA cara dipakai, dan keduanya ditentukan oleh kolom
 * "visibilitas" (lihat pakaiKode()):
 *
 *   1. Mode kode (private). Pembuat memberi kode di langkah Pengaturan wizard,
 *      lalu orang lain yang mengetik kode itu masuk ke lobby yang sama. Quiz
 *      begini tidak pernah tayang di halaman Quiz dan tidak pernah ikut
 *      ditinjau admin: yang baseado adalah pemegang kodenya, bukan semua
 *      pengguna. Statusnya boleh tetap draft dan sesinya tetap bisa dibuka.
 *
 *   2. Mode publik. Quiz ini tayang untuk semua pengguna, jadi harus lewat
 *      persetujuan admin dulu. Statusnya hanya berubah lewat tiga cara:
 *      pemilik mengajukan (ajukanPersetujuan()), admin menyetujui
 *      (setujui()), atau admin menolak (tolak()).
 *
 * Status quiz mengikuti alur persetujuan admin:
 *   draft     = baru dibuat, belum diajukan
 *   pending   = menunggu persetujuan admin
 *   published = sudah disetujui, tampil untuk semua pengguna
 *   rejected  = ditolak admin (lihat catatan_admin)
 */
#[Fillable([
    'dibuat_oleh',
    'pelajaran_id',
    'kelas',
    'judul',
    'slug',
    'deskripsi',
    'durasi',
    'visibilitas',
    'status',
    'kode_akses',
    'catatan_admin',
    'catatan_pengajuan',
    'jumlah_ditolak',
    'dipublish_pada',
    'thumbnail',
    'tingkat_kesulitan',
    'tampilkan_jawaban',
])]
class Quiz extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const VISIBILITAS_PUBLIK = 'public';

    public const VISIBILITAS_PRIVAT = 'private';

    /**
     * Berapa kali quiz boleh ditolak sebelum tidak bisa lagi diajukan.
     *
     * Setelah mencapai batas ini quiz masih boleh dibaca dan diubah
     * pemiliknya, tapi tidak bisa masuk daftar tunggu admin lagi.
     */
    public const BATAS_PENGAJUAN_ULANG = 2;

    /**
     * Tingkat kesulitan sebuah quiz. Nilai ini disamakan dengan
     * Soal::TINGKAT_KESULITAN supaya form quiz dan form soal memakai
     * daftar yang sama.
     */
    public const TINGKAT_MUDAH = 'Mudah';

    public const TINGKAT_SEDANG = 'Sedang';

    public const TINGKAT_SULIT = 'Sulit';

    /**
     * Nama tabel tidak mengikuti default Laravel ("quizzes").
     */
    protected $table = 'tb_quiz';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dipublish_pada' => 'datetime',
            'jumlah_ditolak' => 'integer',
            'tampilkan_jawaban' => 'boolean',
        ];
    }

    /**
     * Urutan tingkat kesulitan, dari paling mudah. Dipakai untuk memuat
     * dropdown "Tingkat Kesulitan" supaya pilihan di server dan di
     * JavaScript tidak bisa berbeda.
     *
     * @return array<int, string>
     */
    public static function tingkatKesulitan(): array
    {
        return [self::TINGKAT_MUDAH, self::TINGKAT_SEDANG, self::TINGKAT_SULIT];
    }

    /**
     * Kunci jawaban perlu ditampilkan ke peserta setelah quiz selesai.
     *
     * Saklar "Tampilkan Jawaban Setelah Selesai" di form buat quiz
     * menulis kolom ini. Default true supaya quiz lama tetap sama
     * perilakunya.
     */
    public function menampilkanJawaban(): bool
    {
        return (bool) $this->tampilkan_jawaban;
    }

    /**
     * Quiz ini dipakai lewat kode, bukan tayang untuk semua pengguna.
     *
     * Owner tunggalnya adalah pemilik kode: hanya dia yang boleh membuka
     * sesi, dan hanya dia yang boleh memulai atau mengakhiri quiz. Mode ini
     * tidak pernah ikut persetujuan admin, jadi statusnya boleh tetap draft.
     */
    public function pakaiKode(): bool
    {
        return $this->visibilitas === self::VISIBILITAS_PRIVAT;
    }

    /**
     * Kode yang harus diketik peserta untuk masuk ke quiz ini.
     *
     * Dikembalikan dalam bentuk baku: huruf besar tanpa spasi, tanda hubung,
     * dan garis bawah, karena itulah yang dibandingkan saat peserta menekan
     * tombol gabung.
     */
    public function kodeGabung(): string
    {
        return self::kodeBaku($this->kode_akses);
    }

    /**
     * Bentuk baku kode gabung, dipakai controller dan form.
     *
     * Satu-satunya aturannya ada di App\Support\KodeQuiz, supaya kode yang
     * diketik peserta, kode yang disimpan di kolom kode_akses, dan kode yang
     * tampil di lobby tidak bisa berbeda bentuk.
     */
    public static function kodeBaku(?string $kode): string
    {
        return KodeQuiz::normalisasi($kode);
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    /**
     * Quiz yang sudah disetujui admin, jadi aman tampil untuk semua pengguna.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Terbitkan quiz ini sekarang juga.
     *
     * Dipakai oleh menu "Konten Pembelajaran" di area admin: admin adalah
     * pembuat sekaligus penerbit, jadi tidak ada tahap menunggu keputusan
     * siapa pun. Quiz langsung menjadi "published" dan tanggal terbitnya
     * diisi.
     *
     * Nilai yang disimpan sama persis dengan setujui() di bawah, tapi
     * dipisah supaya maksudnya terbaca dari tempat dipanggil: setujui()
     * milik halaman Verifikasi, terbitkan() milik admin yang menerbitkan
     * karyanya sendiri.
     */
    public function terbitkan(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PUBLISHED,
            'dipublish_pada' => now(),
            'catatan_admin' => null,
        ])->save();
    }

    /**
     * Tarik quiz yang sudah tayang kembali menjadi draft.
     *
     * Dipakai ketika admin menekan "Batalkan Publikasi" dari daftar Konten
     * Pembelajaran, dan juga ketika quiz yang tayang diubah menjadi mode
     * kode: mode kode tidak pernah tayang di halaman Quiz, jadi quiz yang
     * sudah terbit tidak boleh ikut terlihat di sana setelah diganti.
     *
     * Hanya turun dari "published". Status lain tidak disentuh supaya alasan
     * penolakan dan hitungan pengajuan ulang tidak ikut hilang hanya karena
     * pemilik atau admin mengganti cara aksesnya.
     */
    public function tarikDariDaftar(): void
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_DRAFT,
            'dipublish_pada' => null,
        ])->save();
    }

    /**
     * Quiz yang sudah diajukan dan sedang menunggu keputusan admin.
     *
     * Dipakai halaman "Tinjau Quiz" di area admin.
     */
    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Quiz yang punya kode gabung.
     *
     * Kode dibersihkan lebih dulu supaya "ab 12" dan "AB12" tetap menemukan
     * quiz yang sama. Pencocokan di Postgres tetap case sensitive, jadi kode
     * di form disimpan selalu huruf besar dan di sini juga diseragamkan dulu.
     */
    public function scopeKodeAkses(Builder $query, ?string $kode): Builder
    {
        return $query->where('kode_akses', self::kodeBaku($kode));
    }

    /**
     * Quiz milik satu pengguna. Dipakai halaman "Karya Saya" supaya quiz
     * milik orang lain tidak pernah ikut tampil, dan supaya quiz yang masih
     * draft atau ditolak tetap bisa dikelola pemiliknya.
     */
    public function scopeMilik(Builder $query, ?int $idPembuat): Builder
    {
        return $query->where('dibuat_oleh', $idPembuat);
    }

    /**
     * Satu-satunya sumber kebenaran untuk "¿ini karya saya?".
     *
     * Dipakai halaman detail, form edit, dan hapus supaya satu tempat yang
     * menentukan apakah pengguna yang sedang login berhak mengelola quiz ini.
     */
    public function dimilikiOleh(?int $idPengguna): bool
    {
        return $idPengguna !== null && (int) $this->dibuat_oleh === $idPengguna;
    }

    /**
     * Apakah quiz ini perlu, dan boleh, ikut alur persetujuan admin.
     *
     * Hanya quiz mode publik yang perlu ditinjau: quiz mode kode tidak pernah
     * tayang untuk semua pengguna, jadi tidak ada yang perlu disetujui.
     */
    public function perluPersetujuan(): bool
    {
        return ! $this->pakaiKode();
    }

    /**
     * Pemilik boleh mengajukan quiz ini ke admin atau tidak.
     *
     * Tiga status bisa masuk daftar tunggu: draft (baru dibuat), quiz yang
     * ditolak, dan quiz yang sudah terbit lalu direvisi. Dua status terakhir
     * sama-sama berarti "perubahannya ikut perlu ditinjau", jadi keduanya
     * memakai satu jalan yang sama.
     *
     * Quiz yang sedang menunggu tidak bisa diajukan lagi, dan quiz yang sudah
     * ditolak BATAS_PENGAJUAN_ULANG kali tidak boleh masuk daftar tunggu untuk
     * ketiga kalinya. Quiz mode kode selalu mengembalikan false: tidak ada
     * admin yang perlu menyetujuinya.
     */
    public function bolehDiajukan(): bool
    {
        if (! $this->perluPersetujuan()) {
            return false;
        }

        $siapDiajukan = in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_REJECTED,
        ], true);

        if (! $siapDiajukan) {
            return false;
        }

        /*
         * Batas pengajuan ulang hanya menahan quiz yang belum pernah lolos
         * review. Quiz yang sudah terbit tidak ikut dihitung: memperbaiki quiz
         * yang sudah tayang bukan percobaan mengulang yang gagal, dan memakai
         * jatah yang sama akan membuat quiz yang berhasil terbit ikut
         * terkunci setelah dua kali ditolak.
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
     * Hanya berlaku untuk quiz yang ditolak admin: di situ admin sudah
     * menuliskan alasan penolakannya, dan catatan pemiliklah yang menjelaskan
     * kenapa perbaikannya kini layak dipublikasikan. Quiz yang belum pernah
     * dinilai tidak dimintai catatan sama sekali, sehingga isian itu pun tidak
     * ditampilkan di form.
     */
    public function perluCatatanPengajuan(): bool
    {
        return $this->bolehDiajukan() && $this->status === self::STATUS_REJECTED;
    }

    /**
     * Apakah quiz ini pernah tayang, jadi revisinya perlu ditinjau lagi.
     */
    public function pernahTerbit(): bool
    {
        return $this->dipublish_pada !== null;
    }

    /**
     * Pemilik mengajukan quiz ini ke admin.
     *
     * Catatan ditolak admin sengaja dikosongkan: begitu quiz diajukan ulang,
     * catatan lama tidak lagi relevan karena isinya sudah diperbaiki. Tanggal
     * terbit juga dibersihkan supaya quiz yang sedang menunggu keputusan tidak
     * terlihat punya tanggal terbit; tanggal baru hanya diisi lagi ketika admin
     * menyetujui.
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
     * Admin menyetujui quiz ini, lalu quiz langsung tayang.
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
     * Admin menolak quiz ini dan menuliskan alasannya.
     *
     * Penghitung ditolak bertambah satu supaya halaman "Karya Saya" bisa
     * memberi tahu sisa berapa kali quiz ini masih boleh diajukan lagi.
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
     * Pencarian quiz di halaman Quiz.
     *
     * Mencari di judul, deskripsi, dan nama mata pelajarannya. Karakter %
     * dan _ dari user di-escape supaya tidak jadi wildcard.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        if ($kataKunci === '') {
            return $query;
        }

        /*
         * PostgreSQL membuat LIKE tidak peduli huruf besar-kecil. Tanpa
         * ILIKE, mengetik "html" tidak akan menemukan judul "HTML & CSS Dasar".
         */
        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pola, $operator) {
            $query->where('judul', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhereHas('pelajaran', fn (Builder $pelajaran) => $pelajaran->where('nama', $operator, $pola));
        });
    }

    /**
     * Filter kategori berdasarkan slug mata pelajaran.
     *
     * Bentuknya sama dengan App\Models\Materi::scopeKategori() supaya
     * halaman Materi dan halaman Quiz menyaring kategori dengan cara yang sama.
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
     * Jumlah soal aktif milik quiz ini.
     *
     * Dipakai untuk badge "10 Soal" pada kartu. Nilai hasil withCount()
     * disimpan di atribut "jumlah_soal_termuat" supaya query daftar tidak
     * perlu satu query tambahan per kartu.
     */
    public function jumlahSoal(): int
    {
        return (int) ($this->jumlah_soal_termuat ?? $this->soal()->aktif()->count());
    }

    /**
     * Filter kelas tujuan, dipakai filter "Semua Kelas" di daftar Konten
     * Pembelajaran.
     *
     * Bentuknya sama persis dengan App\Models\Materi::scopeKelas() supaya
     * materi dan quiz menyaring kelas dengan cara yang sama.
     */
    public function scopeKelas(Builder $query, ?string $kelas): Builder
    {
        $kelas = trim((string) $kelas);

        if ($kelas === '') {
            return $query;
        }

        $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where('kelas', $operator, addcslashes($kelas, '%_\\'));
    }

    /**
     * Nama kelas tujuan, atau null kalau belum ditentukan.
     *
     * Sumbernya sama dengan Materi::labelKelas() supaya lencana kelas pada
     * materi dan pada quiz selalu ditulis dengan aturan yang sama.
     */
    public function labelKelas(): ?string
    {
        $kelas = trim((string) $this->kelas);

        return $kelas === '' ? null : $kelas;
    }

    /**
     * Label status untuk ditampilkan pada halaman detail dan kartu
     * "Karya Saya", supaya user tahu quiznya sudah tayang atau masih
     * menunggu admin.
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
     * Modifier warna untuk lencana status, dipakai sebagai kelas CSS
     * karya-status--{nilai} di kartu "Karya Saya" dan di kepala form
     * ubah quiz.
     *
     * Nilainya harus sama dengan yang dipakai Materi::warnaStatus()
     * supaya satu kelas lencana cukup untuk dua jenis karya.
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
}
