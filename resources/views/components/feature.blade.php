@props([
    'name',
])

@php
    $licentra = app(\Licentra\LicentraLaravel\LicentraLaravel::class);
    $hasFeature = $licentra->hasFeature($name);
@endphp

@if($hasFeature)
    {{ $slot }}
@endif
