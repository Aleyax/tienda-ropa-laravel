<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Configuraciones Generales</h2>
    </x-slot>

    <div class="p-6">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-semibold mb-2">Monto mínimo de primera compra mayorista (S/)</label>
                <input type="number" name="wholesale_first_order_min"
                    value="{{ old('wholesale_first_order_min', $settings['wholesale_first_order_min'] ?? 160) }}"
                    step="0.01" min="0"
                    class="border p-2 w-full rounded">
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="show_out_of_stock" value="1"
                        @checked(old('show_out_of_stock', $settings['show_out_of_stock'] ?? false))>
                    <span class="ml-2">Mostrar productos agotados en el catálogo</span>
                </label>
            </div>

            <div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
