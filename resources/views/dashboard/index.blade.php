@extends('layouts.app')

@section('title', 'Dashboard ELS Retail')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header del Dashboard -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard ELS Retail</h1>
        <p class="mt-2 text-gray-600">Monitoreo en tiempo real de etiquetas, productos y estaciones base</p>
        <div class="mt-4 flex items-center space-x-4">
            <div class="flex items-center text-sm text-gray-500">
                <i class="fas fa-clock mr-2"></i>
                Última actualización: <span id="last-updated" class="ml-1 font-medium">{{ $metrics['last_updated'] ?? 'N/A' }}</span>
            </div>
            <button id="refresh-btn" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-sync-alt mr-2"></i>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Cards de Métricas Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total de Etiquetas -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg hover:shadow-2xl transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-tags text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total de Etiquetas</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="total-etiquetas">{{ number_format($metrics['total_etiquetas'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Etiquetas Vinculadas -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg hover:shadow-2xl transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-link text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Etiquetas Vinculadas</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="etiquetas-vinculadas">{{ number_format($metrics['etiquetas_vinculadas'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Productos -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg hover:shadow-2xl transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-box text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total de Productos</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="total-productos">{{ number_format($metrics['total_productos'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- AP en Línea -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg hover:shadow-2xl transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-wifi text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">AP en Línea</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="ap-online">{{ number_format($metrics['ap_online'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Actividades Recientes -->
    <div class="bg-white overflow-hidden shadow-xl rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Actividad Reciente</h3>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center text-sm text-gray-500">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                        Auto-actualización cada 30s
                    </div>
                </div>
            </div>
        </div>
        
        <div class="divide-y divide-gray-200" id="activities-container">
            @if(count($activities) > 0)
                @foreach($activities as $activity)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center 
                                    @if($activity['color'] === 'green') bg-green-100 text-green-600
                                    @elseif($activity['color'] === 'red') bg-red-100 text-red-600
                                    @elseif($activity['color'] === 'blue') bg-blue-100 text-blue-600
                                    @elseif($activity['color'] === 'yellow') bg-yellow-100 text-yellow-600
                                    @else bg-gray-100 text-gray-600 @endif">
                                    <i class="{{ $activity['icon'] }} text-sm"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                                <p class="text-sm text-gray-500">{{ $activity['description'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $activity['timestamp']->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">No hay actividades recientes</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Indicador de Estado de Conexión -->
    <div class="mt-6 flex justify-center">
        <div id="connection-status" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium">
            <i class="fas fa-circle mr-2"></i>
            <span>Verificando conexión...</span>
        </div>
    </div>
</div>

<!-- Scripts para funcionalidad dinámica -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let refreshInterval;
    
    // Función para actualizar métricas
    function updateMetrics() {
        fetch('/dashboard/metrics')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-etiquetas').textContent = new Intl.NumberFormat().format(data.data.total_etiquetas);
                    document.getElementById('etiquetas-vinculadas').textContent = new Intl.NumberFormat().format(data.data.etiquetas_vinculadas);
                    document.getElementById('total-productos').textContent = new Intl.NumberFormat().format(data.data.total_productos);
                    document.getElementById('ap-online').textContent = new Intl.NumberFormat().format(data.data.ap_online);
                    document.getElementById('last-updated').textContent = data.data.last_updated;
                }
            })
            .catch(error => {
                console.error('Error actualizando métricas:', error);
            });
    }
    
    // Función para actualizar actividades
    function updateActivities() {
        fetch('/dashboard/activities')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('activities-container');
                    if (data.data.length > 0) {
                        container.innerHTML = data.data.map(activity => `
                            <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center 
                                            ${getColorClasses(activity.color)}">
                                            <i class="${activity.icon} text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">${activity.title}</p>
                                        <p class="text-sm text-gray-500">${activity.description}</p>
                                        <p class="text-xs text-gray-400 mt-1">${formatTimestamp(activity.timestamp)}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">No hay actividades recientes</p>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => {
                console.error('Error actualizando actividades:', error);
            });
    }
    
    // Función para verificar conexión
    function checkConnection() {
        fetch('/dashboard/test-connection')
            .then(response => response.json())
            .then(data => {
                const statusElement = document.getElementById('connection-status');
                if (data.success) {
                    statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800';
                    statusElement.innerHTML = '<i class="fas fa-check-circle mr-2"></i><span>Conexión establecida</span>';
                } else {
                    statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800';
                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i><span>Error de conexión</span>';
                }
            })
            .catch(error => {
                const statusElement = document.getElementById('connection-status');
                statusElement.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800';
                statusElement.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i><span>Error de conexión</span>';
            });
    }
    
    // Función auxiliar para colores
    function getColorClasses(color) {
        switch(color) {
            case 'green': return 'bg-green-100 text-green-600';
            case 'red': return 'bg-red-100 text-red-600';
            case 'blue': return 'bg-blue-100 text-blue-600';
            case 'yellow': return 'bg-yellow-100 text-yellow-600';
            default: return 'bg-gray-100 text-gray-600';
        }
    }
    
    // Función para formatear timestamp
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Hace un momento';
        if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
    }
    
    // Botón de actualización manual
    document.getElementById('refresh-btn').addEventListener('click', function() {
        updateMetrics();
        updateActivities();
        checkConnection();
    });
    
    // Inicializar
    checkConnection();
    
    // Auto-actualización cada 30 segundos
    refreshInterval = setInterval(() => {
        updateMetrics();
        updateActivities();
    }, {{ config('dashboard.auto_refresh_interval', 30) * 1000 }});
    
    // Limpiar intervalo al salir de la página
    window.addEventListener('beforeunload', () => {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
});
</script>
@endsection 