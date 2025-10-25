@extends('admin.layout')
@section('title', $mode==='create' ? 'Nuevo Producto' : "Editar: {$product->name}")

@section('content')
  <form method="POST" action="{{ $mode==='create' ? route('admin.products.store') : route('admin.products.update',$product) }}">
    @csrf @if($mode==='edit') @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Nombre</label>
        <input name="name" class="border p-2 w-full" value="{{ old('name',$product->name) }}" required>
      </div>
      <div>
        <label class="block text-sm">Slug</label>
        <input name="slug" class="border p-2 w-full" value="{{ old('slug',$product->slug) }}" required>
      </div>
      <div>
        <label class="block text-sm">Estado</label>
        <select name="status" class="border p-2 w-full">
          @foreach(['active','draft','archived'] as $st)
            <option value="{{ $st }}" @selected(old('status',$product->status)===$st)>{{ $st }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm">Precio base (minorista)</label>
        <input type="number" step="0.01" min="0" name="price_base" class="border p-2 w-full"
               value="{{ old('price_base',$product->price_base) }}" required>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm">Descripción</label>
        <textarea name="description" class="border p-2 w-full" rows="3">{{ old('description',$product->description) }}</textarea>
      </div>
    </div>

    <div class="mt-3">
      <button class="bg-blue-600 text-white px-4 py-2 rounded">
        {{ $mode==='create' ? 'Crear' : 'Guardar' }}
      </button>
      <a href="{{ route('admin.products.index') }}" class="ml-2 px-3 py-2 border rounded">Volver</a>
    </div>
  </form>
@endsection

@if($mode==='edit')
<hr class="my-6">

{{-- Variantes --}}
<h3 class="font-semibold mb-2">Variantes (color/talla)</h3>

<form method="POST" action="{{ route('admin.variants.store',$product) }}" class="grid md:grid-cols-5 gap-2 mb-3">
  @csrf
  <select name="color_id" class="border p-2" required>
    <option value="">Color</option>
    @foreach($colors as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
  </select>
  <select name="size_id" class="border p-2" required>
    <option value="">Talla</option>
    @foreach($sizes as $s) <option value="{{ $s->id }}">{{ $s->code }}</option> @endforeach
  </select>
  <input name="sku" class="border p-2" placeholder="SKU" required>
  <input type="number" name="stock" min="0" class="border p-2" value="0" required>
  <input type="number" step="0.01" min="0" name="price_base" class="border p-2" placeholder="Precio base (opcional)">
  <div class="md:col-span-5">
    <button class="bg-gray-800 text-white px-3 py-2 rounded">Agregar variante</button>
  </div>
</form>

<table class="min-w-full bg-white border mb-6">
  <thead><tr class="bg-gray-50">
    <th class="p-2 border">Color</th>
    <th class="p-2 border">Talla</th>
    <th class="p-2 border">SKU</th>
    <th class="p-2 border">Stock</th>
    <th class="p-2 border">Precio base (variante)</th>
    <th class="p-2 border">Acciones</th>
  </tr></thead>
  <tbody>
  @forelse($product->variants as $v)
    <tr>
      <td class="p-2 border">{{ $v->color->name }}</td>
      <td class="p-2 border">{{ $v->size->code }}</td>
      <td class="p-2 border">
        <form method="POST" action="{{ route('admin.variants.update',$v) }}" class="flex flex-wrap gap-2 items-center">
          @csrf @method('PUT')
          <input name="sku" class="border p-1 w-40" value="{{ $v->sku }}">
          <input name="barcode" class="border p-1 w-32" value="{{ $v->barcode }}">
          <input name="stock" type="number" min="0" class="border p-1 w-24" value="{{ $v->stock }}">
          <input name="price_base" type="number" step="0.01" min="0" class="border p-1 w-28" value="{{ $v->price_base }}">
          <button class="px-2 py-1 bg-gray-800 text-white rounded text-sm">Guardar</button>
        </form>
      </td>
      <td class="p-2 border"></td>
      <td class="p-2 border"></td>
      <td class="p-2 border">
        <form method="POST" action="{{ route('admin.variants.destroy',$v) }}" onsubmit="return confirm('¿Eliminar variante?')">
          @csrf @method('DELETE')
          <button class="text-red-600">Eliminar</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td class="p-2 border" colspan="6">Sin variantes aún.</td></tr>
  @endforelse
  </tbody>
</table>

{{-- Imágenes por color --}}
<h3 class="font-semibold mb-2">Imágenes</h3>
<form method="POST" action="{{ route('admin.media.store',$product) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 mb-3">
  @csrf
  <select name="color_id" class="border p-2">
    <option value="">(Sin color específico)</option>
    @foreach($colors as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
  </select>
  <label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_primary" value="1"> Principal
  </label>
  <input type="file" name="file" class="border p-2" required>
  <button class="bg-gray-800 text-white px-3 py-2 rounded">Subir</button>
</form>

<div class="grid md:grid-cols-5 gap-3">
  @forelse($product->media as $m)
    <div class="border p-2 rounded">
      <img src="{{ $m->url }}" class="w-full h-40 object-cover mb-2">
      <div class="text-xs text-gray-600">
        {{ $m->color?->name ?? '—' }} {{ $m->is_primary ? ' (principal)' : '' }}
      </div>
      <form method="POST" action="{{ route('admin.media.destroy',$m) }}" onsubmit="return confirm('¿Eliminar imagen?')">
        @csrf @method('DELETE')
        <button class="text-red-600 text-sm">Eliminar</button>
      </form>
    </div>
  @empty
    <div class="text-sm text-gray-500">Aún no hay imágenes.</div>
  @endforelse
</div>
@endif
