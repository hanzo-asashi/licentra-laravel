@php
    $licentra = app(\Licentra\LicentraLaravel\LicentraLaravel::class);
    $isValid = $licentra->ping();
@endphp

@if($isValid)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400']) }}>
        <svg class="h-1.5 w-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3"/></svg>
        {{ __('Active') }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400']) }}>
        <svg class="h-1.5 w-1.5 fill-red-500" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3"/></svg>
        {{ __('Invalid / Expired') }}
    </span>
@endif
