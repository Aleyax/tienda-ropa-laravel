<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">Admin — @yield('title', 'Panel')</h2>
    @hasanyrole('admin|vendedor')
    @php
      $pendingCount = \App\Models\PickBasketTransfer::where('to_user_id', auth()->id())
        ->where('status', 'pending')
        ->count();
    @endphp
    {{--
    <a href="{{ route('admin.baskets.transfers') }}"
      class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.baskets.transfers') ? 'bg-gray-200 font-semibold' : '' }}">
      Transferencias de Canastas
      @if($pendingCount > 0)
      <span class="ml-2 text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-900">
        {{ $pendingCount }}
      </span>
      @endif --}}
    </a>
    <li class="mt-2">
      <a href="{{ route('admin.baskets.mine') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100
              {{ request()->routeIs('admin.baskets.mine') ? 'bg-gray-100 font-semibold' : '' }}">
        <span>Mis canastas</span>

        {{-- Badge de canastas abiertas mías --}}
        @php $openCnt = (int) ($basketMenuCounts['myOpenBaskets'] ?? 0); @endphp
        @if($openCnt > 0)
          <span class="ml-auto inline-flex items-center justify-center text-xs
                         rounded-full bg-gray-200 text-gray-800 px-2 h-5">
            {{ $openCnt }}
          </span>
        @endif
      </a>
    </li>

    <li>
      <a href="{{ route('admin.baskets.transfers') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100
              {{ request()->routeIs('admin.baskets.transfers') ? 'bg-gray-100 font-semibold' : '' }}">
        <span>Transferencias</span>

        {{-- Badge de transferencias pendientes hacia mí --}}
        @php $pendCnt = (int) ($basketMenuCounts['pendingTransfers'] ?? 0); @endphp
        @if($pendCnt > 0)
          <span class="ml-auto inline-flex items-center justify-center text-xs
                         rounded-full bg-amber-500 text-white px-2 h-5">
            {{ $pendCnt }}
          </span>
        @endif
      </a>
    </li>
    @endhasanyrole

  </x-slot>

  <div class="p-6">
    @if(session('success'))
      <div class="mb-4 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
    @endif
    @yield('content')
  </div>
</x-app-layout>