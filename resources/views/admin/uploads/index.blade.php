@extends('admin.layouts.admin')

@section('title', 'Uploads')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-file-upload text-purple-500 mr-2"></i>
        Uploads (todos los tenants)
    </h1>
    <span class="text-sm text-gray-500">{{ $uploads->total() }} registros</span>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.uploads.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Organización</label>
            <select name="organization_id"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Todas</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" @selected(request('organization_id') == $org->id)>
                    {{ $org->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Shop code</label>
            <select name="shop_code"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Todos</option>
                @foreach($shopCodes as $code)
                <option value="{{ $code }}" @selected(request('shop_code') == $code)>
                    {{ $code }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
            <select name="status"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Todos</option>
                <option value="pending"          @selected(request('status') == 'pending')>Pendiente</option>
                <option value="processing"       @selected(request('status') == 'processing')>Procesando</option>
                <option value="completed"        @selected(request('status') == 'completed')>Completado</option>
                <option value="failed"           @selected(request('status') == 'failed')>Error</option>
                <option value="pending_approval" @selected(request('status') == 'pending_approval')>Requiere aprobación</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            @if(request()->hasAny(['organization_id', 'shop_code', 'status']))
            <a href="{{ route('admin.uploads.index') }}"
               class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Tabla -->
@php
$statusColors = [
    'pending'          => 'bg-yellow-100 text-yellow-800',
    'pending_approval' => 'bg-orange-100 text-orange-800',
    'processing'       => 'bg-blue-100 text-blue-800',
    'completed'        => 'bg-green-100 text-green-800',
    'failed'           => 'bg-red-100 text-red-800',
];
$statusLabels = [
    'pending'          => 'Pendiente',
    'pending_approval' => 'Requiere aprobación',
    'processing'       => 'Procesando',
    'completed'        => 'Completado',
    'failed'           => 'Error',
];
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organización</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tienda</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progreso</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($uploads as $upload)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-500">#{{ $upload->id }}</td>

                    <td class="px-4 py-3">
                        @if($upload->organization)
                            <a href="{{ route('admin.organizations.show', $upload->organization) }}"
                               class="text-blue-600 hover:underline text-sm font-medium">
                                {{ $upload->organization->name }}
                            </a>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900 max-w-[200px] truncate" title="{{ $upload->original_filename }}">
                            {{ $upload->original_filename }}
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded font-mono">
                            {{ $upload->shop_code }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $statusColors[$upload->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$upload->status] ?? ucfirst($upload->status) }}
                        </span>
                    </td>

                    <td class="px-4 py-3 min-w-[140px]">
                        @if($upload->total_products > 0)
                        <div class="text-xs text-gray-500 mb-1">
                            {{ $upload->processed_products }} / {{ $upload->total_products }} productos
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full
                                @if($upload->status === 'completed') bg-green-500
                                @elseif($upload->status === 'failed') bg-red-500
                                @else bg-blue-500 @endif"
                                style="width: {{ $upload->progress_percentage }}%">
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $upload->user?->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                        {{ $upload->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                        No hay uploads con los filtros seleccionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($uploads->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $uploads->links() }}
    </div>
    @endif
</div>
@endsection
