{{--
    Dialog kecil "Bagikan Quiz" di halaman detail quiz.

    Muncul hanya kalau browser tidak punya Web Share API (navigator.share).
    Kalau ada, tombol "Bagikan" memanggil navigator.share() langsung dan
    dialog ini tidak pernah tampil (lihat initBagikan() di
    resources/js/quiz-detail.js).

    Dialog-nya memakai .dialog-bab yang sama dengan dialog hapus karya dan
    dialog hapus bab, jadi gayanya tidak terasa seperti elemen asing. Kotaknya
    sengaja tidak besar: yang dibutuhkan cuma satu baris tautan dan satu tombol.

    Kolom tautannya diisi JavaScript dari location.href. Tanpa JavaScript
    tombol "Bagikan" memang tidak melakukan apa-apa (tidak ada API-nya), jadi
    tautan di dalam dialog ditulis sebagai nilai awal supaya tetap bisa dibaca
    dan disalin manual.
--}}

<div class="dialog-bab" data-bagikan-dialog role="dialog" aria-modal="true"
    aria-labelledby="judul-bagikan" aria-describedby="pesan-bagikan">

    <div class="w-full max-w-sm rounded-2xl border border-lavender bg-white p-5 shadow-[0_30px_60px_-30px_rgba(33,26,58,0.8)]">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-lavender text-primary-dark"
            aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
            </svg>
        </span>

        <h2 id="judul-bagikan" class="mt-4 text-base font-extrabold text-dark">Bagikan Quiz</h2>

        <p id="pesan-bagikan" class="mt-1.5 text-sm leading-relaxed text-dark/55">
            Kirim tautan ini ke teman yang mau mengerjakan quiz yang sama.
        </p>

        <label for="tautan-bagikan" class="sr-only">Tautan quiz</label>

        <input type="text" id="tautan-bagikan" readonly data-bagikan-tautan
            class="detail-bagikan__tautan mt-4"
            value="{{ $attributes->get('data-tautan') }}">

        <p class="mt-2 min-h-[1.25rem] text-xs font-semibold text-primary-dark" data-bagikan-status
            role="status" aria-live="polite"></p>

        <div class="mt-3 flex flex-col gap-2.5 sm:flex-row">
            <button type="button" data-bagikan-salin class="detail-bagikan__salin">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m0 0h1.5a5.25 5.25 0 0 1 5.25 5.25v1.5" />
                </svg>

                Salin Link
            </button>

            <button type="button" data-bagitutup class="detail-bagitutup sm:ml-auto">Tutup</button>
        </div>
    </div>
</div>
