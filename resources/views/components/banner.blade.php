@props([
    'showIfValid' => false,
])

@php
    $licentra = app(\Licentra\LicentraLaravel\LicentraLaravel::class);
    $isValid = $licentra->ping();
@endphp

@if(!$isValid)
    <div {{ $attributes->merge(['class' => 'p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 flex items-center justify-between shadow-sm']) }}>
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm font-medium">
                {{ __('Lisensi aplikasi tidak valid atau telah kedaluwarsa. Silakan perbarui lisensi Anda.') }}
            </span>
        </div>
    </div>
@elseif($showIfValid)
    <div {{ $attributes->merge(['class' => 'p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300 flex items-center justify-between shadow-sm']) }}>
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-sm font-medium">
                {{ __('Lisensi aplikasi aktif dan terverifikasi.') }}
            </span>
        </div>
    </div>
@endif
