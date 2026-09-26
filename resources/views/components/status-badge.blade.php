@props(['status'])

@php
    $map = [
        'pending' => 'bg-gold-100 text-gold-800 ring-gold-300',
        'interview' => 'bg-primary-100 text-primary-800 ring-primary-300',
        'hired' => 'bg-primary-600 text-white ring-primary-600',
        'rejected' => 'bg-red-100 text-red-700 ring-red-200',
        'open' => 'bg-primary-600 text-white ring-primary-600',
        'closed' => 'bg-gray-100 text-gray-600 ring-gray-200',
        'active' => 'bg-primary-600 text-white ring-primary-600',
        'completed' => 'bg-gold-100 text-gold-800 ring-gold-300',
    ];
    $labels = [
        'pending' => 'Pending',
        'interview' => 'For Interview',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
        'open' => 'Open',
        'closed' => 'Closed',
        'active' => 'Active',
        'completed' => 'Completed',
    ];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $map[$status] ?? 'bg-gray-100 text-gray-700 ring-gray-200' }}">
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
