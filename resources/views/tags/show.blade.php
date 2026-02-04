@extends('layouts.app')

@section('title', 'Etiqueta ' . $tag['tag_id'] . ' - ELS Retail Updater')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-2">
                <a href="{{ route('tags.index') }}" class="hover:text-blue-600">Etiquetas</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">{{ $tag['tag_id'] }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-tag text-blue-600 mr-2"></i>
                Detalle de Etiqueta
            </h1>
        </div>
        <div class="flex space-x-3">
            <button onclick="refreshTag()" id="refreshBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                <i class="fas fa-sync-alt mr-2"></i>
                Refrescar
            </button>
            <a href="{{ route('tags.index') }}"
                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Información General -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Información General
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">ID Etiqueta:</dt>
                    <dd class="font-mono font-medium">{{ $tag['tag_id'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo:</dt>
                    <dd class="font-medium">{{ $tag['tag_type'] ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Estado:</dt>
                    <dd>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ match($tag['status_text']) {
                                'Normal', 'Heartbeat' => 'bg-green-100 text-green-800',
                                'Batería baja' => 'bg-yellow-100 text-yellow-800',
                                'Comunicando' => 'bg-blue-100 text-blue-800',
                                'Sospecha perdida' => 'bg-orange-100 text-orange-800',
                                'Fallo final' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            } }}">
                            {{ $tag['status_text'] }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">AP Predeterminado:</dt>
                    <dd class="font-medium">{{ $tag['default_ap'] ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Template:</dt>
                    <dd class="font-medium">{{ $tag['template'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Producto Vinculado -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-box text-green-600 mr-2"></i>
                Producto Vinculado
            </h2>
            @if($tag['goods_code'])
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Código:</dt>
                        <dd class="font-mono font-medium">{{ $tag['goods_code'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-1">Nombre:</dt>
                        <dd class="font-medium text-gray-800">{{ $tag['goods_name'] }}</dd>
                    </div>
                </dl>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-unlink text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">Sin producto vinculado</p>
                </div>
            @endif
        </div>

        <!-- Estado de Batería -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-battery-half text-yellow-600 mr-2"></i>
                Batería
            </h2>
            <div class="text-center">
                <div class="relative inline-flex items-center justify-center w-24 h-24">
                    <svg class="w-24 h-24 transform -rotate-90">
                        <circle cx="48" cy="48" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                        <circle cx="48" cy="48" r="40"
                            stroke="{{ $tag['power_percent'] >= 60 ? '#22c55e' : ($tag['power_percent'] >= 30 ? '#eab308' : '#ef4444') }}"
                            stroke-width="8" fill="none"
                            stroke-linecap="round"
                            stroke-dasharray="{{ 251.2 * $tag['power_percent'] / 100 }} 251.2"/>
                    </svg>
                    <span class="absolute text-2xl font-bold">{{ $tag['power_percent'] }}%</span>
                </div>
                <p class="text-gray-500 mt-2">Valor: {{ $tag['power_val'] ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Señal -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-signal text-blue-600 mr-2"></i>
                Señal
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">RSSI:</dt>
                    <dd class="font-medium">{{ $tag['rssi'] ?? 'N/A' }} dBm</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Calidad:</dt>
                    <dd>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ match($tag['signal_quality']) {
                                'Excelente', 'Muy buena' => 'bg-green-100 text-green-800',
                                'Buena' => 'bg-blue-100 text-blue-800',
                                'Regular' => 'bg-yellow-100 text-yellow-800',
                                'Débil', 'Muy débil' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            } }}">
                            {{ $tag['signal_quality'] }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Temperatura:</dt>
                    <dd class="font-medium">{{ $tag['temperature'] ?? 'N/A' }}°C</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Estadísticas de Comunicación -->
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-exchange-alt text-purple-600 mr-2"></i>
            Comunicación
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-800">{{ $tag['total_send'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Total Envíos</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-{{ ($tag['error_count'] ?? 0) > 0 ? 'red' : 'gray' }}-800">{{ $tag['error_count'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Errores</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-800">{{ $tag['last_send_time'] ? \Carbon\Carbon::parse($tag['last_send_time'])->format('d/m/Y H:i') : 'N/A' }}</p>
                <p class="text-sm text-gray-500">Último Envío</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-800">{{ $tag['last_recv_time'] ? \Carbon\Carbon::parse($tag['last_recv_time'])->format('d/m/Y H:i') : 'N/A' }}</p>
                <p class="text-sm text-gray-500">Última Recepción</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function refreshTag() {
    if (!confirm('¿Deseas refrescar esta etiqueta?')) return;

    const btn = document.getElementById('refreshBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
    btn.disabled = true;

    fetch('{{ route('tags.refresh', $tag['tag_id']) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error de conexión');
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i>${message}`;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}
</script>
@endsection
