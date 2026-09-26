@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman materi" class="mt-8 flex flex-wrap items-center justify-center gap-1.5">
        @if ($paginator->onFirstPage())
            <span
                class="flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-full border border-lavender bg-white text-dark/25"
                aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-lavender bg-white text-dark/60 transition hover:-translate-y-0.5 hover:border-primary hover:text-primary"
                aria-label="Halaman sebelumnya">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="flex h-10 min-w-10 items-center justify-center px-1 text-sm font-semibold text-dark/30">
                    {{ $element }}
                </span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                            class="flex h-10 min-w-10 items-center justify-center rounded-full bg-primary px-3 text-sm font-bold text-white shadow-[0_12px_24px_-14px_rgba(108,77,230,0.9)]">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                            class="flex h-10 min-w-10 items-center justify-center rounded-full border border-lavender bg-white px-3 text-sm font-semibold text-dark/65 transition hover:-translate-y-0.5 hover:border-primary hover:text-primary">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-lavender bg-white text-dark/60 transition hover:-translate-y-0.5 hover:border-primary hover:text-primary"
                aria-label="Halaman selanjutnya">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        @else
            <span
                class="flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-full border border-lavender bg-white text-dark/25"
                aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        @endif
    </nav>
@endif
