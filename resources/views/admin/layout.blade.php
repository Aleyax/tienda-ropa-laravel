<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">Admin — @yield('title', 'Panel')</h2>
    @php
      $pendingCount = \App\Models\PickBasketTransfer::where('to_user_id', auth()->id())
        ->where('status', 'pending')
        ->count();
    @endphp
    <a href="{{ route('admin.baskets.transfers') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
      Transferencias de Canastas
      @if($pendingCount > 0)
        <span class="ml-1 text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-900">{{ $pendingCount }}</span>
      @endif
    </a>
  </x-slot>

  <div class="p-6">
    @if(session('success'))
      <div class="mb-4 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
    @endif
    @yield('content')
  </div>
</x-app-layout>