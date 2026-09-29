@php
    /*
     * Isian jawaban untuk satu soal di halaman mengerjakan quiz.
     *
     * Enam tipe soal, satu berkas. Yang membedakan hanya bentuk isiannya,
     * sedangkan kerangkanya sama: judul kecil, satu kalimat petunjuk, lalu
     * areanya sendiri.
     *
     * Nama field sengaja mengikuti yang dibaca SesiKerjakanController:
     *   - empat tipe berdaftar  -> name="jawaban"
     *   - dua tipe teks        -> name="jawaban_teks"
     *
     * Input asli disembunyikan (sr-only atau tersembunyi) dan diganti
     * tampilan sendiri, supaya pilihan terpilih tetap tampil benar tanpa
     * JavaScript. Untuk dropdown dan kolom teks, bentuk aslinya dipakai
     * apa adanya karena keduanya sudah punya gaya kelas .kolom-form.
     *
     * @var \App\Models\Soal $soal
     * @var string $tipe
     * @var array<string, string> $pilihan     * @var array<int, string>|string $terpilih
     * @var string $teksJawaban
     * @var string $petunjuk
     */
    use App\Models\Soal;

    $banyak = $tipe === Soal::TIPE_PILIHAN_BANYAK;
    $teks = in_array($tipe, [Soal::TIPE_JAWABAN_SINGKAT, Soal::TIPE_PARAGRAF], true);

    /*
     * Benar / Salah sudah tertulis sendiri di tiap pilihan, jadi huruf A dan
     * B di depannya hanya mengulang dan membingungkan. Tipe lain tetap
     * memakainya karena teks pilihannya bisa apa saja.
     */
    $tampilHuruf = $tipe !== Soal::TIPE_BENAR_SALAH;
    $terpilihHuruf = array_map('strval', is_array($terpilih) ? $terpilih : [$terpilih]);
@endphp

<p class="soal-kartu__label-form">{{ $petunjuk }}</p>

@if ($teks)
    {{-- Jawaban singkat: satu baris. --}}
    @if ($tipe === Soal::TIPE_JAWABAN_SINGKAT)
        <label for="soal-jawaban-teks" class="sr-only">Jawaban kamu</label>

        <input id="soal-jawaban-teks" type="text" name="jawaban_teks" maxlength="500"
            value="{{ $teksJawaban }}" autocomplete="off" data-soal-isian
            placeholder="Tulis jawabanmu di sini..."
            class="soal-kolom mt-2">
    @else
        {{--
            Paragraf: isian dibuat tinggi supaya peserta tidak perlu
            menggulir halaman di tengah menulis. Height tetap, bukan
            rows, supaya tidak ikut melebar saat pertanyaan pendek.
        --}}
        <label for="soal-jawaban-teks" class="sr-only">Jawaban kamu</label>

        <textarea id="soal-jawaban-teks" name="jawaban_teks" rows="6" maxlength="2000"
            data-soal-isian placeholder="Tulis jawabanmu di sini..."
            class="soal-kolom soal-kolom--luas mt-2">{{ $teksJawaban }}</textarea>
    @endif
@elseif ($tipe === Soal::TIPE_DROPDOWN)
    {{--
        Dropdown memakai select aslinya karena ini satu-satunya tempat di
        halaman ini yang butuh kontrol native; panahnya diganti lewat
        .pilih-bungkus yang dipakai form builder, supaya tampilannya sama
        dengan dropdown di halaman lain.
    --}}
    <label for="soal-jawaban-pilih" class="sr-only">Jawaban kamu</label>

    <div class="pilih-bungkus mt-2">
        <select id="soal-jawaban-pilih" name="jawaban"
            class="kolom-form pilih-form" data-soal-isian>
            <option value="">Pilih jawaban...</option>

            @foreach ($pilihan as $huruf => $isi)
                <option value="{{ $huruf }}" @selected(in_array($huruf, $terpilihHuruf, true))>
                    {{ $huruf }}. {{ $isi }}
                </option>
            @endforeach
        </select>

        <span class="pilih-bungkus__panah" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </span>
    </div>
@else
    {{--
        Pilihan ganda, pilihan banyak, dan benar/salah memakai kartu yang
        sama; yang membedakan jenis input-nya dan apakah huruf depannya
        ditampilkan. Radio dan checkbox aslinya disembunyikan, dan keadaan
        terpilih dibaca lewat :has() supaya gaya kartu tidak bergantung
        pada JavaScript sama sekali.
    --}}
    <div class="mt-2 space-y-2.5">
        @foreach ($pilihan as $huruf => $isi)
            <label class="lobi-pilihan">
                <input type="{{ $banyak ? 'checkbox' : 'radio' }}" name="jawaban{{ $banyak ? '[]' : '' }}"
                    value="{{ $huruf }}" class="sr-only"
                    @checked(in_array($huruf, $terpilihHuruf, true))>

                @if ($tampilHuruf)
                    <span class="lobi-pilihan__huruf" aria-hidden="true">{{ $huruf }}</span>
                @endif

                <span class="min-w-0 pt-1 text-sm leading-relaxed text-dark">{{ $isi }}</span>
            </label>
        @endforeach
    </div>
@endif
