@php
    $colors = [
        'gray' => 'bg-gray-100 text-gray-800',
        'green' => 'bg-green-100 text-green-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'purple' => 'bg-purple-100 text-purple-800',
        'cyan' => 'bg-cyan-100 text-cyan-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
    ];

    $class = $colors[$type] ?? $colors['gray'];
@endphp

<span class="px-2 py-0.5 rounded text-xs font-medium {{ $class }}">
    {{ $text }}
</span>
