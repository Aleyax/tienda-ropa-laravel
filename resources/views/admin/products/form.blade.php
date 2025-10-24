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
