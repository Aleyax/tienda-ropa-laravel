@extends('admin.layout')
@section('title','Productos')

@section('content')
<form method="GET" class="mb-3 flex gap-2">
    <input type="text" name="s" value="{{ request('s') }}" class="border p-2" placeholder="Buscar nombre o slug">
    <button class="px-3 border rounded">Buscar</button>
    <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded">Nuevo</a>
</form>

<table class="min-w-full bg-white border">
    <thead>
        <tr class="bg-gray-50">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Nombre</th>
            <th class="p-2 border">Slug</th>
            <th class="p-2 border">Estado</th>
            <th class="p-2 border">Precio base</th>
            <th class="p-2 border">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $p)
        <tr>
            <td class="p-2 border">{{ $p->id }}</td>
            <td class="p-2 border">{{ $p->name }}</td>
            <td class="p-2 border">{{ $p->slug }}</td>
            <td class="p-2 border">{{ $p->status }}</td>
            <td class="p-2 border">S/ {{ number_format($p->price_base,2) }}</td>
            <td class="p-2 border">
                <a class="text-blue-600" href="{{ route('admin.products.edit',$p) }}">Editar</a>
                <form method="POST" action="{{ route('admin.products.destroy',$p) }}" class="inline" onsubmit="return confirm('¿Eliminar producto?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 ml-2">Eliminar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td class="p-2 border" colspan="6">Sin productos.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-3">{{ $rows->links() }}</div>
@endsection