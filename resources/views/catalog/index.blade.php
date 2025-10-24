<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Catálogo</h2>
    </x-slot>
    <div class="p-6 grid md:grid-cols-3 gap-4">
        @foreach($products as $p)
        <a href="/producto/{{ $p->slug }}" class="border rounded p-3 block">
            <img src="{{ optional($p->media->firstWhere('is_primary',true))?->url ?? $p->media->first()->url ?? 'https://via.placeholder.com/400x400' }}" class="w-full mb-2" />
            <div class="font-semibold">{{ $p->name }}</div>
        </a>
        @endforeach
    </div>
</x-app-layout>