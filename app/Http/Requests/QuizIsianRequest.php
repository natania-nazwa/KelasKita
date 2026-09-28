<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Isian form quiz: dipakai oleh form buat quiz dan form edit quiz milik
 * sendiri, jadi aturan soal-soalnya hanya ditulis satu kali di sini.
 *
 * *_soal dikirim sebagai array angka (soal[0][pertanyaan], soal[0][pilihan_a],
 * dst.) supaya satu form bisa memuat banyak soal sekaligus.
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

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pelajaran_id' => ['required', Rule::exists('tb_pelajaran', 'id')->where('aktif', true)],
            'judul' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:220'],
            'durasi' => ['nullable', 'integer', 'min:1', 'max:600'],
            'visibilitas' => ['required', Rule::in([Quiz::VISIBILITAS_PUBLIK, Quiz::VISIBILITAS_PRIVAT])],
            'kode_akses' => [
                'nullable',
                'string',
                'min:4',
                'max:20',
                'alpha_dash',
                /*
                 * Kode privat harus unik supaya tidak bisa menabrak quiz lain.
                 * Saat mengedit, kode milik quiz itu sendiri diabaikan,
                 * kalau tidak quizzes tidak akan bisa menyimpan kode yang
                 * tidak pernah diubah.
                 */
                Rule::unique('tb_quiz', 'kode_akses')->ignore($this->route('quiz')),
            ],

            'soal' => ['required', 'array', 'min:'.self::MINIMAL_SOAL, 'max:'.self::MAKSIMAL_SOAL],
            // Satu baris soal harus berupa array, bukan teks: tanpa aturan ini
            // baris seperti soal[0]=abc lolos dari pemeriksaan jumlah.
            'soal.*' => ['array'],
            'soal.*.pertanyaan' => ['required', 'string', 'max:255'],
            'soal.*.pilihan_a' => ['required', 'string', 'max:255'],
            'soal.*.pilihan_b' => ['required', 'string', 'max:255'],
            'soal.*.pilihan_c' => ['required', 'string', 'max:255'],
            'soal.*.pilihan_d' => ['required', 'string', 'max:255'],
            'soal.*.jawaban_benar' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'soal.*.pembahasan' => ['nullable', 'string', 'max:500'],
            'soal.*.tingkat_kesulitan' => ['required', Rule::in(['Mudah', 'Sedang', 'Sulit'])],
        ];
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
            'deskripsi.max' => 'Deskripsi maksimal 220 karakter.',
            'durasi.integer' => 'Durasi harus berupa angka menit.',
            'durasi.min' => 'Durasi minimal 1 menit.',
            'durasi.max' => 'Durasi maksimal 600 menit.',
            'visibilitas.in' => 'Pilih siapa saja yang boleh mengerjakan quiz ini.',
            'kode_akses.min' => 'Kode akses minimal 4 karakter.',
            'kode_akses.alpha_dash' => 'Kode akses hanya boleh memakai huruf, angka, tanda hubung, dan garis bawah.',
            'kode_akses.unique' => 'Kode akses sudah dipakai quiz lain.',
            'soal.required' => 'Quiz minimal harus punya satu soal.',
            'soal.min' => 'Quiz minimal harus punya satu soal.',
            'soal.max' => 'Maksimal '.self::MAKSIMAL_SOAL.' soal per quiz.',
            'soal.*.array' => 'Setiap baris soal harus diisi lengkap.',
            'soal.*.pertanyaan.required' => 'Pertanyaan setiap soal wajib diisi.',
            'soal.*.pilihan_a.required' => 'Pilihan A setiap soal wajib diisi.',
            'soal.*.pilihan_b.required' => 'Pilihan B setiap soal wajib diisi.',
            'soal.*.pilihan_c.required' => 'Pilihan C setiap soal wajib diisi.',
            'soal.*.pilihan_d.required' => 'Pilihan D setiap soal wajib diisi.',
            'soal.*.jawaban_benar.in' => 'Jawaban benar harus A, B, C, atau D.',
            'soal.*.tingkat_kesulitan.in' => 'Tingkat kesulitan tidak dikenal.',
        ];
    }

    /**
     * Isian quiz siap disimpan, setelah dibersihkan dari field yang tidak
     * relevan.
     *
     * Kode privat disimpan, kode publik dikosongkan supaya tidak ada kode
     * unik terisi sia-sia.
     *
     * @return array<string, mixed>
     */
    public function isian(): array
    {
        $isian = $this->validated();

        if ($isian['visibilitas'] === Quiz::VISIBILITAS_PUBLIK) {
            $isian['kode_akses'] = null;
        }

        return $isian;
    }
}
