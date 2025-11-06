<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subir comprobante de pago
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-6">
        <div class="bg-white shadow p-6 rounded">
            <form action="{{ route('orders.payments.store', $order) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <label class="block text-sm font-medium mb-1">Monto</label>
                <input type="number" step="0.01" name="amount" class="border p-2 w-full mb-4" required>

                <label class="block text-sm font-medium mb-1">Método</label>
                <select name="method" class="border p-2 w-full mb-4" required>
                    <option value="Yape">Yape</option>
                    <option value="Plin">Plin</option>
                    <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                </select>

                <label class="block text-sm font-medium mb-1">Comprobante (imagen o PDF)</label>
                <input type="file" name="evidence" accept="image/*,application/pdf" class="block mt-1 mb-4">

                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Subir Pago
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
