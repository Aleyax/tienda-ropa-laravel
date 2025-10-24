<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">Admin — @yield('title','Panel')</h2>
  </x-slot>

  <div class="p-6">
    @if(session('success'))
      <div class="mb-4 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
    @endif
    @yield('content')
  </div>
</x-app-layout>
