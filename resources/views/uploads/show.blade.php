{{-- resources/views/uploads/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Upload #' . $upload->id)

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Upload #{{ $upload->id }}</h1>
                    <p class="text-gray-600">{{ $upload->original_filename }}</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('uploads.download', $upload) }}"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Descargar Original
                    </a>
                    <a href="{{ route('uploads.report', $upload) }}"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Ver Reporte
                    </a>
                    @if($upload->status === 'completed')
                        <form method="POST" action="{{ route('uploads.refresh-tags', $upload) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                                Actualizar Etiquetas
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensajes flash -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-800 rounded-lg">
                {{ session('info') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="mb-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Resultado del refresh automático post-carga --}}
        @if($upload->status === 'completed' && $upload->tags_refreshed !== null)
            <div class="mb-6 p-4 bg-purple-100 border border-purple-400 text-purple-800 rounded-lg">
                @if($upload->tags_refreshed > 0)
                    Actualización automática de etiquetas completada: <strong>{{ $upload->tags_refreshed }}</strong> etiqueta(s) refrescada(s).
                @else
                    Actualización automática completada: no se encontraron etiquetas vinculadas a los productos de esta carga.
                @endif
            </div>
        @endif

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Total
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ number_format($statistics['total']) }}
                    </dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Procesados
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-blue-600">
                        {{ number_format($statistics['procesados']) }}
                    </dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Creados
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-green-600">
                        {{ number_format($statistics['creados']) }}
                    </dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Actualizados
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-yellow-600">
                        {{ number_format($statistics['actualizados']) }}
                    </dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Omitidos
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-500">
                        {{ number_format($statistics['omitidos']) }}
                    </dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Errores
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-red-600">
                        {{ number_format($statistics['errores']) }}
                    </dd>
                </div>
            </div>
        </div>

        <!-- Barra de progreso -->
        @if ($upload->status === 'processing')
            <div class="bg-white shadow rounded-lg p-6 mb-6" x-data="progressUpdater()">
                <h3 class="text-lg font-medium mb-4">Progreso</h3>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="h-4 rounded-full transition-all duration-500"
                         :class="statusFailed ? 'bg-red-600' : 'bg-blue-600'"
                         :style="`width: ${progress}%`">
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <span x-text="processed"></span> de <span x-text="total"></span> productos procesados
                    (<span x-text="progress"></span>%)
                </p>
                <p x-show="statusFailed" class="text-sm text-red-600 mt-1 font-medium"
                   x-text="'Error: ' + errorMessage"></p>
            </div>
        @endif

        <!-- Logs detallados -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-4">Detalle de Productos</h3>

                <!-- Filtros -->
                <div class="mb-4 flex space-x-4">
                    <select
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        onchange="applyFilter('esl', this.value)">
                        <option value="">Todas las etiquetas</option>
                        <option value="vinculados" {{ request('esl') == 'vinculados' ? 'selected' : '' }}>Con etiqueta ESL</option>
                        <option value="sin_vincular" {{ request('esl') == 'sin_vincular' ? 'selected' : '' }}>Sin etiqueta ESL</option>
                    </select>

                    <select
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        onchange="applyFilter('status', this.value)">
                        <option value="">Todos los estados</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Exitosos</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Con errores</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                    </select>

                    <select
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        onchange="applyFilter('action', this.value)">
                        <option value="">Todas las acciones</option>
                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Creados</option>
                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Actualizados</option>
                        <option value="skipped" {{ request('action') == 'skipped' ? 'selected' : '' }}>Omitidos</option>
                    </select>
                </div>

                <script>
                function applyFilter(key, value) {
                    const params = new URLSearchParams(window.location.search);
                    if (value) {
                        params.set(key, value);
                    } else {
                        params.delete(key);
                    }
                    params.delete('page');
                    window.location.href = '{{ route('uploads.show', $upload) }}?' + params.toString();
                }
                </script>

                <!-- Tabla de logs -->
                <div class="overflow-x-auto"> 
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" title="Etiqueta ESL vinculada">
                                    ESL
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID TAG
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Código Barras
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Código Interno
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Precios
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                @php $tagId = $tagMapping[$log->product_variant_id] ?? null; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        @if($tagId)
                                            <span title="Etiqueta vinculada: {{ $tagId }}" class="inline-flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-gray-300" title="Sin etiqueta vinculada">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-purple-600 font-bold">
                                        {{ $log->product_variant_id ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                        {{ $log->productVariant->cod_barras ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-blue-600 font-medium">
                                        {{ $log->productVariant->codigo_interno ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                        <div class="truncate" title="{{ $log->productVariant->descripcion ?? 'N/A' }}">
                                            {{ $log->productVariant->descripcion ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($log->productVariant && $log->productVariant->product)
                                            <div class="space-y-1">
                                                {{-- 🎯 NUEVO FORMATO DE PRECIOS COMO LA VISTA ANTERIOR - DESDE TABLA PRODUCT --}}
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-500">Original:</span>
                                                    <span class="font-medium">${{ number_format($log->productVariant->product->precio_final, 2) }}</span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-500">Descuento:</span>
                                                    <span class="font-medium text-green-600">${{ number_format($log->productVariant->product->precio_calculado, 2) }}</span>
                                                </div>
                                                {{-- 🎯 MOSTRAR PRECIO ANTERIOR SI EXISTE --}}
                                                @if(isset($log->precio_anterior_eretail) && $log->precio_anterior_eretail)
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-500">Anterior:</span>
                                                        <span class="text-xs text-gray-400">${{ number_format($log->precio_anterior_eretail, 2) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @switch($log->status)
                                            @case('success')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Exitoso
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Error
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pendiente
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($log->status) }}
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @switch($log->action)
                                            @case('created')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Creado
                                                </span>
                                                @break
                                            @case('updated')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Actualizado
                                                </span>
                                                @break
                                            @case('skipped')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Omitido
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($log->action) }}
                                                </span>
                                        @endswitch
                                    </td>
                                </tr>
                                @if($log->status === 'failed' && $log->error_message)
                                    <tr class="bg-red-50">
                                        <td colspan="8" class="px-6 py-2 text-sm text-red-600">
                                            <strong>Error:</strong> {{ $log->error_message }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        No se encontraron registros
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    @if ($upload->status === 'processing')
        <script>
            function progressUpdater() {
                return {
                    progress: 0,
                    processed: 0,
                    total: 0,
                    statusFailed: false,
                    errorMessage: '',
                    polling: null,
                    init() {
                        // Disparar queue worker (fire-and-forget)
                        fetch(`/__run_queue?key={{ config('app.queue_key') }}`).catch(() => {});
                        this.updateProgress();
                        this.polling = setInterval(() => {
                            this.updateProgress();
                        }, 2000);
                    },
                    async updateProgress() {
                        try {
                            const response = await fetch(`/uploads/{{ $upload->id }}/progress`);
                            const data = await response.json();
                            this.progress = data.progress_percentage || 0;
                            this.processed = data.processed || 0;
                            this.total = data.total_products || 0;

                            if (data.status === 'failed') {
                                this.statusFailed = true;
                                this.errorMessage = data.error_message || 'Error desconocido';
                                clearInterval(this.polling);
                                setTimeout(() => window.location.reload(), 3000);
                            } else if (data.is_complete) {
                                clearInterval(this.polling);
                                window.location.reload();
                            }
                        } catch (error) {
                            console.error('Error fetching progress:', error);
                        }
                    }
                }
            }
        </script>
    @endif
@endsection