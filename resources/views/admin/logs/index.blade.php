@extends('admin.layouts.admin')

@section('title', 'Logs de Actividad')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-list-alt text-yellow-500 mr-2"></i> Logs de Actividad
    </h1>
    <p class="text-gray-500 text-sm mt-1">Registro de eventos de negocio y errores técnicos del sistema</p>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.logs') }}" class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">

        {{-- Tipo --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
            <select name="type" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <option value="">Todos</option>
                @foreach(\App\Models\ActivityLog::TYPES as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Nivel --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nivel</label>
            <select name="level" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <option value="">Todos</option>
                @foreach(\App\Models\ActivityLog::LEVELS as $l)
                    <option value="{{ $l }}" @selected(request('level') === $l)>{{ ucfirst($l) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Organización --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Organización</label>
            <select name="org_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <option value="">Todas</option>
                @foreach($organizations as $org)
                    <option value="{{ $org->id }}" @selected(request('org_id') == $org->id)>{{ $org->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Desde --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
        </div>

        {{-- Hasta --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
        </div>

        {{-- Botones --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium px-3 py-1.5 rounded transition">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            <a href="{{ route('admin.logs') }}"
               class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium px-3 py-1.5 rounded transition">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </div>
</form>

{{-- Conteo de resultados --}}
<div class="text-sm text-gray-500 mb-3">
    {{ number_format($logs->total()) }} registro(s) encontrado(s) — página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}
</div>

{{-- Tabla --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Fecha/Hora</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Org</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Nivel</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Evento</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Detalles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {{ $log->created_at->format('d/m H:i:s') }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $log->organization?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $log->user?->email ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $typeColors = [
                                'auth'    => 'bg-blue-100 text-blue-700',
                                'upload'  => 'bg-purple-100 text-purple-700',
                                'tags'    => 'bg-teal-100 text-teal-700',
                                'settings'=> 'bg-orange-100 text-orange-700',
                                'eretail' => 'bg-pink-100 text-pink-700',
                                'system'  => 'bg-gray-100 text-gray-700',
                            ];
                            $typeColor = $typeColors[$log->type] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColor }}">
                            {{ $log->type }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $levelColors = [
                                'info'    => 'bg-green-100 text-green-700',
                                'warning' => 'bg-yellow-100 text-yellow-700',
                                'error'   => 'bg-red-100 text-red-700',
                            ];
                            $levelColor = $levelColors[$log->level] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $levelColor }}">
                            {{ $log->level }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700">
                        {{ $log->event }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $log->description }}">
                        {{ $log->description }}
                    </td>
                    <td class="px-4 py-3">
                        @if($log->properties)
                        <button type="button"
                                onclick="toggleDetails('details-{{ $log->id }}')"
                                class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">
                            [+] ver
                        </button>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                {{-- Fila expandible de detalles --}}
                @if($log->properties)
                <tr id="details-{{ $log->id }}" class="hidden bg-gray-50">
                    <td colspan="8" class="px-6 py-3">
                        <pre class="text-xs text-gray-600 bg-white border border-gray-200 rounded p-3 overflow-auto max-h-48 whitespace-pre-wrap">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                        <i class="fas fa-inbox text-2xl mb-2 block"></i>
                        No hay registros para los filtros seleccionados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($logs->hasPages())
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<script>
function toggleDetails(id) {
    const row = document.getElementById(id);
    if (row) {
        row.classList.toggle('hidden');
    }
}
</script>
@endsection
