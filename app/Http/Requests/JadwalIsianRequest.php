<?php

namespace App\Http\Requests;

use App\Models\Jadwal;
use App\Support\DaftarJadwal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Isian form jadwal: dipakai oleh halaman tambah jadwal dan form edit jadwal
 * milik sendiri, jadi aturannya hanya ditulis satu kali di sini.
 *
 * Yang paling penting dari isian ini bukan bentuknya, tapi tiga hal: jadwalnya
 * tidak boleh saling tumpang tindih, PR yang ada harus selalu punya tenggat,
 * dan semua field selain hari, jam, pelajaran, dan nama pelajaran boleh
 * dikosongkan tanpa akibat apa pun.
 *
 * Kelolaan (apakah jadwal ini boleh diubah pemiliknya) tidak dicek di sini:
 * jadwal dicari dari route, jadi pemeriksaannya dilakukan di controller yang
 * sama-sama sudah memegang modelnya.
 */
class JadwalIsianRequest extends FormRequest
{
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
            'hari' => ['required', 'integer', 'between:0,6'],
            'mulai' => ['required', 'date_format:H:i'],
            'selesai' => ['required', 'date_format:H:i', 'after:mulai'],

            'pelajaran' => [
                'required',
                'string',
                Rule::in(array_column(DaftarJadwal::pilihanPelajaran(), 'slug')),
            ],

            'judul' => ['required', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:60'],
            'ruang' => ['nullable', 'string', 'max:60'],

            /*
             * PR menempel pada jam pelajaran. Kalau salah satu diisi, yang
             * lain wajib ikut: PR tanpa tenggat tidak bisa dikejar, dan
             * tenggat tanpa PR tidak mungkin dimejaikan.
             *
             * Pengecekannya lewat teks() supaya isian yang cuma berisi spasi
             * tetap dianggap kosong, sama seperti saat disimpan.
             */
            'pr' => ['nullable', 'string', 'max:500', Rule::requiredIf($this->filled('pr_dikumpulkan'))],
            'pr_dikumpulkan' => [
                'nullable',
                'date',
                Rule::requiredIf(fn (): bool => $this->teks('pr') !== null),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hari.required' => 'Pilih hari jadwalnya.',
            'hari.between' => 'Hari yang dipilih tidak dikenal.',
            'mulai.required' => 'Jam mulai wajib diisi.',
            'mulai.date_format' => 'Jam mulai harus ditulis sebagai HH:MM.',
            'selesai.required' => 'Jam selesai wajib diisi.',
            'selesai.date_format' => 'Jam selesai harus ditulis sebagai HH:MM.',
            'selesai.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'pelajaran.required' => 'Pilih mata pelajaran.',
            'pelajaran.in' => 'Mata pelajaran yang dipilih tidak dikenal.',
            'judul.required' => 'Nama pelajaran wajib diisi.',
            'judul.max' => 'Nama pelajaran maksimal 100 karakter.',
            'kelas.max' => 'Nama kelas maksimal 60 karakter.',
            'ruang.max' => 'Nama ruang maksimal 60 karakter.',
            'pr.max' => 'Isi PR maksimal 500 karakter.',
            'pr.required' => 'Tulis dulu isi PRnya, baru tentukan tanggal dikumpulkan.',
            'pr_dikumpulkan.required' => 'Tentukan kapan PR itu harus dikumpulkan.',
            'pr_dikumpulkan.date' => 'Tanggal dikumpulkan tidak valid.',
        ];
    }

    /**
     * Tolak jam yang tumpang tindih dengan jadwal lain milik pengguna yang
     * sama pada hari yang sama.
     *
     * Dua jadwal dianggap tumpang tindih kalau jadwal lama mulai sebelum
     * jadwal baru selesai DAN jadwal lama selesai setelah jadwal baru mulai.
     * Pembandingannya ketat (tidak memakai <=), jadi pelajaran 08:00-09:30 dan
     * 09:30-10:30 tetap boleh berdampingan.
     *
     * Jam dibandingkan sebagai "HH:MM:SS" karena itulah bentuk yang dipakai
     * saat menyimpan, dan bentuknya sama persis di SQLite maupun PostgreSQL.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $jadwal = $this->jadwal();

            $bentrok = Jadwal::query()
                ->milik($this->user()?->getKey())
                ->where('hari', (int) $this->input('hari'))
                ->where('mulai', '<', $this->jamLengkap('selesai'))
                ->where('selesai', '>', $this->jamLengkap('mulai'))
                ->when($jadwal !== null, fn (Builder $query) => $query->whereKeyNot($jadwal->getKey()))
                ->exists();

            if ($bentrok) {
                $validator->errors()->add('mulai', 'Jam tersebut sudah dipakai pelajaran lain di hari yang sama.');
            }
        });
    }

    /**
     * Isian jadwal siap disimpan.
     *
     * Yang wajib diisi cuma hari, jam, pelajaran, dan nama pelajaran. Semua
     * field lain opsional dan disimpan sebagai null kalau dikosongkan, supaya
     * halaman bisa membedakan "sudah diisi" dari "tidak diisi" dan hanya
     * menampilkan yang memang ada isinya. Karena itu kelas dan ruang tidak
     * lagi diberi nilai default di sini: defaultnya dihapus dari database,
     * bukan dipindah ke sini.
     *
     * Jam dinormalkan ke "HH:MM:SS" supaya bentuknya sama persis dengan yang
     * dibaca kembali, apa pun driver databasenya.
     *
     * @return array<string, mixed>
     */
    public function isian(): array
    {
        return [
            'hari' => (int) $this->validated('hari'),
            'mulai' => $this->jamLengkap('mulai'),
            'selesai' => $this->jamLengkap('selesai'),
            'pelajaran' => $this->validated('pelajaran'),
            'judul' => $this->validated('judul'),
            'kelas' => $this->teks('kelas'),
            'ruang' => $this->teks('ruang'),
            'pr' => $this->teks('pr'),
            'pr_dikumpulkan' => $this->filled('pr_dikumpulkan') ? $this->validated('pr_dikumpulkan') : null,
        ];
    }

    /**
     * Isian teks opsional, atau null kalau tidak diisi.
     *
     * Spasi di tepi ikut dibuang, jadi " " tetap dihitung sebagai tidak diisi.
     */
    private function teks(string $field): ?string
    {
        $nilai = trim($this->string($field)->toString());

        return $nilai === '' ? null : $nilai;
    }

    /**
     * Jadwal yang sedang diedit, diambil dari route.
     *
     * Route /jadwal/{jadwal} sudah diikat ke model, jadi isinya bisa berupa
     * objek Jadwal, bukan cuma angka. Dua-duanya ditangani supaya jadwal itu
     * sendiri tidak ikut dihitung sebagai jam yang bertumpuk dengan dirinya.
     *
     * Halaman tambah jadwal tidak punya jadwal di route, jadi selalu
     * mengembalikan null di sana.
     */
    private function jadwal(): ?Jadwal
    {
        $jadwal = $this->route('jadwal');

        if ($jadwal instanceof Jadwal) {
            return $jadwal;
        }

        return is_int($jadwal) || is_string($jadwal)
            ? Jadwal::query()->find($jadwal)
            : null;
    }

    /**
     * "HH:MM" dari field request menjadi "HH:MM:SS".
     */
    private function jamLengkap(string $field): string
    {
        return $this->string($field)->toString().':00';
    }
}
