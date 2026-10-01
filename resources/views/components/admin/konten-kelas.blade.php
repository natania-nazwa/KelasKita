@props([
    // Nama ikon di App\Support\Ikon.
    'nama',
    // Nama field yang di isi (mis. "kelas"). Null berarti jangan tampilkan.
    'field' => null,
    // Daftar kelas => label, dari App\Support\KelasKonten::pilihan().
    'pilihan' => [],
    // Nilai kelas yang sedang tersimpan, untuk mode edit.
    'nilai' => null,
    // Label kolom di atas select.
    'label' => 'Kelas',
])

{{--
    Pemilih kelas tujuan untuk form materi dan form quiz di area admin.

    Sengaja komponen sendiri, bukan field baru di dalam
    x-materi.informasi atau x-quiz.wizard-informasi: kedua komponen itu
    dipakai bersama dengan form milik pengguna, dan halaman pengguna tidak
    punya konsep kelas tujuan. Menulis field-nya di sana berarti form
    pengguna ikut berubah — padahal yang perlu-absen di sana adalah form
    admin saja.

    Datanya bukan tabel master melainkan nilai yang benar-benar dipakai
    materi dan quiz (App\Support\KelasKonten), jadi kelas baru bisa muncul
    di kedua dropdown begitu dipakai, tanpa perlu disimpan ulang di tempat
    lain.

    Boleh dikosongkan: konten tanpa kelas tetap bisa dibuat dan terbit.
    Yang berubah hanya lencana kelasnya tidak muncul di daftar.
--}}

@if ($field !== null)
    <div>
        <label for="{{ $field }}" class="label-form">{{ $label }}</label>

        <div class="relative mt-1.5">
            <select id="{{ $field }}" name="{{ $field }}" class="kolom-form pilih-form @error($field) kolom-form--salah @enderror">
                {{--
                    old() didahulukan supaya pilihan yang gagal disimpan
                    kembali setelah validation gagal, termasuk waktu admin
                    mengosongkan sendiri field ini.
                --}}
                <option value="">Tidak ditentukan</option>

                @foreach ($pilihan as $nilaiOpsi => $labelOpsi)
                    <option value="{{ $nilaiOpsi }}"
                        @selected(old($field, $nilai) === $nilaiOpsi)>
                        {{ $labelOpsi }}
                    </option>
                @endforeach
            </select>

            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ungu"
                fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="{{ \App\Support\Ikon::path('panah-bawah') }}" />
            </svg>
        </div>

        <p class="galat-baris" id="{{ $field }}-galat">@error($field) {{ $message }} @enderror</p>

        <p class="mt-1 text-[11px] leading-relaxed text-muted">
            Opsional. Dipakai untuk menyaring konten di daftar, dan tidak membatasi siapa saja yang boleh membacanya.
        </p>
    </div>
@endif
