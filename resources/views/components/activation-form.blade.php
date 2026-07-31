@props([
    'action' => route('licentra.activate'),
])

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'space-y-4 max-w-md p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700']) }}>
    @csrf

    <div>
        <label for="license_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('Kode Lisensi Licentra') }}
        </label>
        <input 
            type="text" 
            name="license_key" 
            id="license_key" 
            placeholder="XXXX-XXXX-XXXX-XXXX"
            value="{{ old('license_key', config('licentra-laravel.license_key')) }}"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white px-3 py-2 border"
        />
        @error('license_key')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    @if(session('licentra_success'))
        <div class="p-3 text-xs text-green-700 bg-green-100 rounded-md dark:bg-green-900/40 dark:text-green-300">
            {{ session('licentra_success') }}
        </div>
    @endif

    <button 
        type="submit"
        class="inline-flex justify-center w-full rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        {{ __('Aktifkan Lisensi') }}
    </button>
</form>
