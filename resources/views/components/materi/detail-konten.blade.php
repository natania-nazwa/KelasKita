@props([
    'seksi' => [],
    'detail',
])

{{--
    Kolom konten materi: seluruh seksi berurutan, dengan seksi latihan
    diganti menjadi kartu latihan. Section/CMS lain tidak perlu tahu
    bentuk datanya, mereka cukup mengirim daftar seksi hasil IsiMateri.
--}}

<div class="min-w-0 space-y-5 sm:space-y-6">
    @foreach ($seksi as $s)
        @if ($s['latihan'])
            <x-materi.detail-latihan :seksi="$s" :judul-materi="$detail['judul']"
                :tautan="$detail['tautan_latihan']" />
        @else
            <x-materi.detail-seksi :seksi="$s" />
        @endif
    @endforeach
</div>
