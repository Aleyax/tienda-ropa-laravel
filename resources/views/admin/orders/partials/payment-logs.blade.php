{{-- Modal: Registrar pago --}}
<div x-data @open-register-payment.window="$refs.modalRegPago.showModal()" x-cloak>
  <dialog x-ref="modalRegPago" class="rounded-lg p-0">
    <form method="dialog">
      <div class="flex justify-between items-center px-4 py-2 border-b">
        <h3 class="font-semibold">Registrar pago</h3>
        <button class="text-sm" @click="$refs.modalRegPago.close()">✕</button>
      </div>
    </form>

    <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" enctype="multipart/form-data" class="p-4 space-y-3">
      @csrf
      <div>
        <label class="block text-sm">Método</label>
        <input type="text" name="method" class="border p-2 w-full" placeholder="Transferencia / Yape / Plin" required>
      </div>
      <div>
        <label class="block text-sm">Monto (S/)</label>
        <input type="number" step="0.01" name="amount" class="border p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm">Referencia bancaria</label>
        <input type="text" name="provider_ref" class="border p-2 w-full">
      </div>
      <div>
        <label class="block text-sm">Comprobante (imagen o PDF)</label>
        <input type="file" name="evidence" class="border p-2 w-full" accept=".jpg,.jpeg,.png,.pdf">
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="px-3 py-1 rounded border" @click="$refs.modalRegPago.close()">Cancelar</button>
        <button class="bg-blue-600 text-white px-3 py-1 rounded">Guardar</button>
      </div>
    </form>
  </dialog>
</div>
