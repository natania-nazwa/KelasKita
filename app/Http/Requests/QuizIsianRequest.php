<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Models\Soal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Isian form quiz: dipakai oleh form builder "Buat Quiz" dan form edit
 * quiz milik sendiri, jadi aturan soal-soalnya hanya ditulis satu kali
 * di sini.
 *
 * Satu form tetap mencakup ketiga tahap wizard (Informasi Dasar, Buat
 * Soal, Pengaturan). JavaScript menyusun isian dari state langkah demi
 * langkah, lalu mengirim semuanya sekaligus seperti form biasa: server
 * tidak perlu tahu halaman mana yang sedang dibuka.
 *
 * Bentuk isian tiap soal mengikuti tipe soalnya, jadi selalu ada tiga field
 * dasar dan dua field opsional yang hanya berarti untuk sebagian tipe:
 *
 *   soal[0][pertanyaan]    teks pertanyaan
 *   soal[0][tipe]           nilai tipe dari Soal::tipeTersedia()
 *   soal[0][pilihan][A]     teks pilihan, hanya untuk tipe berdaftar
 *   soal[0][benar][]        huruf jawaban benar, boleh lebih dari satu
 *                           untuk tipe pilihan banyak
 *   soal[0][jawaban_teks]   kunci jawaban, hanya untuk tipe teks
 *   soal[0][tococok_persis] jawaban singkat harus cocok persis atau tidak
 *
 * Urutannya mengikuti urutan kartu soal di layar, bukan nomor yang diketik
 * manual, jadi nomor soal selalu dihitung ulang dari posisi array.
 *
 * Format lama (soal[0][pilihan_a] dan soal[0][jawaban_benar]) masih
 * diterima dan diterjemahkan lebih dulu di prepareForValidation(), supaya
 * form yang sedang terbuka di tab lain tidak ikut gagal.
 *
 * Field "publikasikan" berarti "pemilik menyalakan saklar Ajukan Persetujuan",
 * bukan "quiz ini harus tayang". Nilainya belum tentu menghasilkan quiz
 * yang tayang: controller menerjemahkannya jadi permintaan persetujuan, dan
 * baru admin yang bisa menyetujuinya. Quiz mode kode tidak ikut aturan ini,
 * karena tidak pernah tayang untuk semua pengguna.
 *
 * Kelolaan (apakah quiz ini boleh diubah pemiliknya) dicek di controller,
 * karena model quiz sudah diambil oleh route model binding.
 */
class QuizIsianRequest extends FormRequest
{
    /** Jumlah minimal soal supaya quiz layak dikerjakan. */
    public const MINIMAL_SOAL = 1;

    /** Batas jumlah soal per quiz, supaya form tidak mengirim tak terbatas. */
    public const MAKSIMAL_SOAL = 30;

    /** Batas ukuran thumbnail quiz: 5MB. */
    public const MAKSIMAL_THUMBNAIL = 5 * 1024;

    /**
     * Terjemahkan format lama ke format baru sebelum divalidasi.
     *
     * Format lama menyimpan pilihan di kolom terpisah (pilihan_a sampai
     * pilihan_f) dan kunci jawaban di satu field (jawaban_benar). Builder
     * yang sekarang mengirim satu larik pilihan dan satu larik huruf benar.
     * Menormalkan di sini membuat seluruh aturan di bawah cukup ditulis
     * untuk format baru saja.
     */
    protected function prepareForValidation(): void
    {
        $soal = $this->input('soal');

        if (! is_array($soal)) {
            return;
        }

        $baru = [];

        foreach ($soal as $baris) {
            if (! is_array($baris)) {
                continue;
            }

            if (! array_key_exists('pilihan', $baris)) {
                $pilihan = [];

                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $huruf) {
                    $isi = trim((string) ($baris['pilihan_'.strtolower($huruf)] ?? ''));

                    if ($isi !== '') {
                        $pilihan[$huruf] = $isi;
                    }
                }

                $baris['pilihan'] = $pilihan;
            }

            if (! array_key_exists('benar', $baris)) {
                $kunci = $baris['jawaban_benar'] ?? null;

                $baris['benar'] = is_string($kunci) && $kunci !== '' ? [$kunci] : [];
            }

            /*
             * Tipe hanya diberi nilai bawaan kalau field-nya memang tidak
             * dikirim (format lama). Tipe yang dikirim tapi isinya tidak
             * dikenal sengaja dibiarkan apa adanya supaya aturan
             * soal.*.tipe menolaknya, bukan diam-diam diganti jadi
             * pilihan ganda.
             */
            if (! array_key_exists('tipe', $baris) || blank($baris['tipe'])) {
                $baris['tipe'] = Soal::TIPE_PILIHAN_GANDA;
            }

            /*
             * Benar / Salah selalu berisi dua pilihan yang sama, jadi
             * pilihannya dibuat dari sini kalau pengirim tidak mengirimnya
             * (format lama, kiriman API). Tanpa ini, aturan minimal dua
             * pilihan akan menolak soal yang isinya sudah lengkap lain.
             */
            if ($baris['tipe'] === Soal::TIPE_BENAR_SALAH && ($baris['pilihan'] ?? []) === []) {
                $baris['pilihan'] = array_combine(['A', 'B'], Soal::PILIHAN_BENAR_SALAH);
            }

            $baru[] = $baris;
        }

        $this->merge(['soal' => $baru]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $aturan = [
            'pelajaran_id' => ['required', Rule::exists('tb_pelajaran', 'id')->where('aktif', true)],
            'judul' => ['required', 'string', 'max:120'],
            'deskripsi' => ['required', 'string', 'max:220'],
            'tingkat_kesulitan' => ['required', Rule::in(Quiz::tingkatKesulitan())],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAKSIMAL_THUMBNAIL],

            'visibilitas' => ['required', Rule::in([Quiz::VISIBILITAS_PUBLIK, Quiz::VISIBILITAS_PRIVAT])],
            // Saklar selalu dikirim form baru, tapi dibuat nullable supaya
            // request lama (tanpa field ini) tetap berarti "tampilkan".
            'tampilkan_jawaban' => ['nullable', 'boolean'],
            'durasi' => ['nullable', 'integer', 'min:1', 'max:600'],
            'kode_akses' => [
                /*
                 * Quiz privat harus punya kode: kode yang tidak ada berarti
                 * tidak ada yang bisa membukanya, jadi ikut wajib. Quiz
                 * publik boleh membiarkan kode kosong.
                 */
                Rule::requiredIf($this->input('visibilitas') === Quiz::VISIBILITAS_PRIVAT),
                'nullable',
                'string',
                'min:4',
                'max:20',
                /*
                 * Hanya huruf dan angka. Tanda hubung dan garis bawah
                 * kelihatan bebas, tapi normalisasiKode() menghapus keduanya
                 * sebelum kode dibandingkan, jadi kode yang memakainya tidak
                 * akan pernah bisa diketik peserta untuk masuk.
                 */
                'alpha_num',
                /*
                 * Kode privat harus unik supaya tidak bisa menabrak quiz lain.
                 * Saat mengedit, kode milik quiz itu sendiri diabaikan,
                 * kalau tidak quiz tidak akan bisa menyimpan kode yang
                 * tidak pernah diubah.
                 */
                Rule::unique('tb_quiz', 'kode_akses')->ignore($this->route('quiz')),
            ],

            /*
             * Saklar "Ajukan Persetujuan" di langkah Pengaturan. Bernilai 1
             * kalau pemilik meminta quiz-nya ditinjau admin, bukan berarti
             * quiz langsung tayang: controller menerjemahkannya jadi permintaan
             * persetujuan, dan baru admin yang bisa menyetujuinya.
             */
            'publikasikan' => ['nullable', 'boolean'],

            /*
             * Catatan pendukung hanya diminta saat quiz yang sudah pernah
             * ditolak diajukan ulang, supaya admin punya alasan menilai apakah
             * perbaikannya sudah cukup.
             */
            'catatan_pengajuan' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => $this->diajukanUlang()),
            ],

            'soal' => ['required', 'array', 'min:'.self::MINIMAL_SOAL, 'max:'.self::MAKSIMAL_SOAL],
            // Satu baris soal harus berupa array, bukan teks: tanpa aturan ini
            // baris seperti soal[0]=abc lolos dari pemeriksaan jumlah.
            'soal.*' => ['array'],
            'soal.*.pertanyaan' => ['required', 'string', 'max:255'],
            'soal.*.tipe' => ['required', Rule::in(Soal::tipeTersedia())],

            /*
             * Pilihan dan kunci jawaban: aturan dasar saja. Aturan yang
             * bergantung tipe — misalnya "tipe pilihan banyak boleh lebih
             * dari satu jawaban benar" — ditulis di after() karena perlu
             * membaca soal.*.tipe di baris yang sama.
             */
            'soal.*.pilihan' => ['sometimes', 'array'],
            /*
             * Pilihan yang dikosongkan tidak dianggap galat: builder
             * mengirim semua huruf, dan yang dikosongkan memang berarti
             * "tidak dipakai". Jumlah pilihan yang benar-benar terisi
             * diawasi after().
             */
            'soal.*.pilihan.*' => ['nullable', 'string', 'max:255'],
            'soal.*.benar' => ['sometimes', 'array'],
            'soal.*.benar.*' => ['string', Rule::in(Soal::hurufTersedia())],

            'soal.*.jawaban_teks' => ['nullable', 'string', 'max:2000'],
            'soal.*.tococok_persis' => ['nullable', 'boolean'],
            'soal.*.pembahasan' => ['nullable', 'string', 'max:500'],
            'soal.*.tingkat_kesulitan' => ['required', Rule::in(Quiz::tingkatKesulitan())],
        ];

        return $aturan;
    }

    /**
     * Pemeriksaan yang tidak bisa ditulis sebagai aturan biasa.
     *
     * Semua aturan di sini bergantung pada tipe soalnya, jadi tidak bisa
     * ditulis dengan Rule::requiredIf: yang berubah bukan cuma field mana
     * yang wajib, tapi juga berapa banyak yang boleh terisi. Contohnya tipe
     * pilihan banyak mengizinkan beberapa jawaban benar, sedangkan tipe lain
     * maksimal satu.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $soal = $this->input('soal');

                if (! is_array($soal)) {
                    return;
                }

                foreach ($soal as $index => $baris) {
                    if (! is_array($baris)) {
                        continue;
                    }

                    $tipe = $baris['tipe'] ?? null;

                    // Tipe yang tidak dikenal sudah ditolak aturan soal.*.tipe.
                    if (! in_array($tipe, Soal::tipeTersedia(), true)) {
                        continue;
                    }

                    $this->periksaTipe($validator, (int) $index, $tipe, $baris);
                }
            },
        ];
    }

    /**
     * Aturan per tipe soal, satu tipe satu blok supaya jelas mana yang
     * berlaku untuk tipe mana.
     *
     * @param  array<string, mixed>  $baris
     */
    private function periksaTipe(Validator $validator, int $index, string $tipe, array $baris): void
    {
        $kunciLokasi = "soal.{$index}";

        // Dua tipe terakhir menjawab dengan teks, jadi tidak punya pilihan.
        if (in_array($tipe, [Soal::TIPE_JAWABAN_SINGKAT, Soal::TIPE_PARAGRAF], true)) {
            // Paragraf menyimpan jawaban acuan, jadi boleh kosong: penilaiannya
            // dibaca manual dan kunci yang kosong tidak membuat quiz tak berguna.
            if ($tipe === Soal::TIPE_JAWABAN_SINGKAT && blank($baris['jawaban_teks'] ?? null)) {
                $validator->errors()->add(
                    "{$kunciLokasi}.jawaban_teks",
                    'Jawaban singkat harus diisi, karena tidak ada pilihan untuk dibandingkan.'
                );
            }

            return;
        }

        $pilihan = array_filter(
            (array) ($baris['pilihan'] ?? []),
            fn ($teks) => trim((string) $teks) !== '',
        );

        $benar = array_values(array_filter(
            (array) ($baris['benar'] ?? []),
            fn ($huruf) => array_key_exists($huruf, $pilihan),
        ));

        if (count($pilihan) < Soal::MINIMAL_PILIHAN) {
            $validator->errors()->add(
                "{$kunciLokasi}.pilihan",
                'Soal bertipe '.Soal::labelTipe($tipe).' minimal punya '.Soal::MINIMAL_PILIHAN.' pilihan jawaban.'
            );

            return;
        }

        if (count($pilihan) > Soal::MAKSIMAL_PILIHAN) {
            $validator->errors()->add(
                "{$kunciLokasi}.pilihan",
                'Maksimal '.Soal::MAKSIMAL_PILIHAN.' pilihan jawaban per soal.'
            );

            return;
        }

        // Satu huruf yang ditandai benar tapi pilihannya tidak ada berarti
        // pembuat soal menandai pilihan yang sudah dihapus.
        $yatim = array_diff(
            array_map('strval', (array) ($baris['benar'] ?? [])),
            array_keys($pilihan),
        );

        if ($yatim !== []) {
            $validator->errors()->add(
                "{$kunciLokasi}.benar",
                'Pilihan '.implode(', ', $yatim).' ditandai benar tapi isinya kosong.'
            );

            return;
        }

        if ($tipe === Soal::TIPE_PILIHAN_BANYAK) {
            if ($benar === []) {
                $validator->errors()->add(
                    "{$kunciLokasi}.benar",
                    'Tandai minimal satu jawaban benar.'
                );
            }

            return;
        }

        if (count($benar) !== 1) {
            $validator->errors()->add(
                "{$kunciLokasi}.benar",
                'Tipe '.Soal::labelTipe($tipe).' hanya boleh punya tepat satu jawaban benar.'
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pelajaran_id.required' => 'Pilih kategori quiz terlebih dahulu.',
            'pelajaran_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'judul.required' => 'Judul quiz wajib diisi.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.max' => 'Deskripsi maksimal 220 karakter.',
            'tingkat_kesulitan.in' => 'Tingkat kesulitan tidak dikenal.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'thumbnail.mimes' => 'Format thumbnail harus JPG, PNG, atau WEBP.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 5MB.',
            'visibilitas.in' => 'Pilih cara publikasi quiz ini.',
            'kode_akses.required' => 'Kode quiz wajib diisi untuk quiz yang memakai kode.',
            'kode_akses.min' => 'Kode akses minimal 4 karakter.',
            'kode_akses.alpha_num' => 'Kode akses hanya boleh memakai huruf dan angka.',
            'kode_akses.unique' => 'Kode akses sudah dipakai quiz lain.',
            'catatan_pengajuan.required' => 'Tuliskan catatan pendukung supaya admin tahu apa yang sudah diperbaiki.',
            'catatan_pengajuan.max' => 'Catatan pengajuan maksimal 500 karakter.',
            'soal.required' => 'Quiz minimal harus punya satu soal.',
            'soal.min' => 'Quiz minimal harus punya satu soal.',
            'soal.max' => 'Maksimal '.self::MAKSIMAL_SOAL.' soal per quiz.',
            'soal.*.array' => 'Setiap baris soal harus diisi lengkap.',
            'soal.*.pertanyaan.required' => 'Pertanyaan setiap soal wajib diisi.',
            'soal.*.tipe.required' => 'Pilih tipe jawaban soal ini.',
            'soal.*.tipe.in' => 'Tipe jawaban tidak dikenal.',
            'soal.*.pilihan.*.required' => 'Pilihan jawaban yang dikosongkan tidak ikut disimpan.',
            'soal.*.pilihan.*.max' => 'Pilihan jawaban maksimal 255 karakter.',
            'soal.*.benar.*.in' => 'Jawaban benar menunjuk pilihan yang tidak ada.',
            'soal.*.tingkat_kesulitan.in' => 'Tingkat kesulitan tidak dikenal.',
            'durasi.integer' => 'Durasi harus berupa angka menit.',
            'durasi.min' => 'Durasi minimal 1 menit.',
            'durasi.max' => 'Durasi maksimal 600 menit.',
        ];
    }

    /**
     * Isian quiz siap disimpan, setelah dibersihkan dari field yang hanya
     * controlling form.
     *
     * Kode hanya disimpan untuk quiz privat: kode publik dikosongkan
     * supaya tidak ada kode unik terisi sia-sia.
     *
     * @return array<string, mixed>
     */
    public function isian(): array
    {
        $isian = $this->validated();

        if ($isian['visibilitas'] === Quiz::VISIBILITAS_PRIVAT) {
            $isian['kode_akses'] = strtoupper((string) $isian['kode_akses']);
        } else {
            $isian['kode_akses'] = null;
        }

        /*
         * Catatan pengajuan hanya berarti kalau quiz ini benar-benar
         * diajukan ke admin. Quiz mode kode tidak pernah ditinjau admin,
         * jadi isian yang mungkin terlupa di form tidak ikut tersimpan.
         */
        if (! $this->diajukanKeAdmin()) {
            $isian['catatan_pengajuan'] = null;
        }

        unset($isian['publikasikan']);

        // Saklar dikirim sebagai "1" / "0"; kolomnya boolean. Field yang
        // tidak dikirim dianggap "tampilkan" supaya request lama tidak
        // diam-diam mengubah perilaku quiz.
        $isian['tampilkan_jawaban'] = $this->has('tampilkan_jawaban')
            ? $this->boolean('tampilkan_jawaban')
            : true;

        return $isian;
    }

    /**
     * Apakah pemilik benar-benar meminta quiz-nya ditinjau admin.
     *
     * Saklar "Ajukan Persetujuan" hanya punya arti untuk quiz mode publik.
     * Quiz mode kode tidak pernah tayang untuk semua pengguna, jadi tidak ada
     * yang perlu disetujui dan permintaannya diabaikan.
     */
    public function diajukanKeAdmin(): bool
    {
        return $this->boolean('publikasikan')
            && $this->input('visibilitas') !== Quiz::VISIBILITAS_PRIVAT;
    }

    /**
     * Quiz yang sedang diedit, diambil dari route.
     *
     * Hanya dipakai untuk tahu apakah quiz ini pernah ditolak, supaya catatan
     * pengajuan jadi wajib atau tidak. Halaman tambah quiz tidak punya quiz,
     * jadi selalu mengembalikan null di sana.
     */
    private function quiz(): ?Quiz
    {
        $dariRoute = $this->route('quiz');

        if ($dariRoute instanceof Quiz) {
            return $dariRoute;
        }

        return is_string($dariRoute) || is_int($dariRoute)
            ? Quiz::query()->find($dariRoute)
            : null;
    }

    /**
     * Permintaan ini adalah pengajuan ulang: quiz yang sudah pernah ditolak,
     * dan kali ini pemiliknya menekan tombol publikasi.
     */
    private function diajukanUlang(): bool
    {
        return $this->diajukanKeAdmin() && (bool) $this->quiz()?->perluCatatanPengajuan();
    }
}
