<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Mis direcciones</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
        @endif

        {{-- Form crear dirección --}}
        <form method="POST" action="{{ route('addresses.store') }}" class="grid md:grid-cols-2 gap-3 bg-white p-4 rounded border">
            @csrf
            <div>
                <label class="block text-sm text-gray-600">Nombre de contacto</label>
                <input type="text" name="contact_name" class="border p-2 w-full" required>
                @error('contact_name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600">Teléfono</label>
                <input type="text" name="phone" class="border p-2 w-full" required>
                @error('phone') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600">Distrito</label>
                <input type="text" name="district" class="border p-2 w-full" required>
                @error('district') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600">Dirección</label>
                <input type="text" name="line1" class="border p-2 w-full" required>
                @error('line1') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Referencia (opcional)</label>
                <input type="text" name="reference" class="border p-2 w-full">
            </div>
            <div class="md:col-span-2">
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Guardar</button>
            </div>
        </form>

        {{-- Lista de direcciones --}}
        <div class="bg-white border rounded">
            <div class="p-3 font-semibold border-b">Direcciones registradas</div>
            <div class="divide-y">
                @forelse($addresses as $address)
                    <div class="p-3 flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $address->contact_name }} — {{ $address->phone }}</div>
                            <div class="text-sm text-gray-600">{{ $address->district }} — {{ $address->line1 }}</div>
                            @if($address->is_default)
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">Predeterminada</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('addresses.destroy', $address) }}"
                              onsubmit="return confirm('¿Eliminar esta dirección?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600">Eliminar</button>
                        </form>
                    </div>
                @empty
                    <div class="p-3 text-sm text-gray-600">Aún no tienes direcciones.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
