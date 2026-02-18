@extends('layouts.app')

@section('title', 'Etiquetas - ESL Retail Updater')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            {{-- <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-tags text-blue-600 mr-2"></i>
                Gestión de Etiquetas
            </h1>
            <p class="text-gray-600 mt-1">Visualiza y gestiona las etiquetas ESL</p> --}}
        </div>
        <button onclick="refreshSelected()" id="refreshSelectedBtn" disabled
            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            <i class="fas fa-sync-alt mr-2"></i>
            Refrescar Seleccionadas
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-link"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Vinculadas</p>
                    <p class="text-xl font-bold text-gray-800">{{ $stats['vinculadas'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                    <i class="fas fa-unlink"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Sin Vincular</p>
                    <p class="text-xl font-bold text-gray-800">{{ $stats['sin_vincular'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-battery-quarter"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Batería Baja</p>
                    <p class="text-xl font-bold text-gray-800">{{ $stats['bateria_baja'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Offline</p>
                    <p class="text-xl font-bold text-gray-800">{{ $stats['offline'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-64">
                <input type="text" id="searchInput" placeholder="Buscar por ID, código o nombre de producto..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="vinculadas">Vinculadas</option>
                    <option value="sin_vincular">Sin vincular</option>
                    <option value="bateria_baja">Batería baja</option>
                </select>
            </div>
            <button onclick="loadTags()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                <i class="fas fa-search mr-2"></i>
                Buscar
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tagsTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Etiqueta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batería</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Señal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Comunicación</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tagsTableBody">
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando etiquetas...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación info -->
    <div class="mt-4 text-sm text-gray-600" id="paginationInfo">
    </div>
</div>

<!-- Modal de confirmación -->
<div id="refreshModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-sync-alt text-blue-600 mr-2"></i>
            Confirmar Actualización
        </h3>
        <p class="text-gray-600 mb-6" id="refreshModalText">
            ¿Deseas actualizar las etiquetas seleccionadas?
        </p>
        <div class="flex justify-end space-x-4">
            <button onclick="closeRefreshModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button onclick="confirmRefresh()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Confirmar
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let tagsData = [];
let selectedTags = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTags();

    // Event listeners
    document.getElementById('selectAll').addEventListener('change', toggleSelectAll);
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') loadTags();
    });
    document.getElementById('statusFilter').addEventListener('change', loadTags);
});

function loadTags() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;

    const tbody = document.getElementById('tagsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Cargando etiquetas...</p>
            </td>
        </tr>
    `;

    fetch(`{{ route('tags.data') }}?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tagsData = data.data;
                renderTable();
                document.getElementById('paginationInfo').textContent = `Mostrando ${data.total} etiquetas`;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                            <p>Error al cargar etiquetas</p>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>Error de conexión</p>
                    </td>
                </tr>
            `;
        });
}

function renderTable() {
    const tbody = document.getElementById('tagsTableBody');

    if (tagsData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p>No se encontraron etiquetas</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = tagsData.map(tag => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">
                <input type="checkbox" class="tag-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    data-tag-id="${tag.tag_id}" onchange="updateSelection()">
            </td>
            <td class="px-4 py-3">
                <span class="font-mono text-sm">${tag.tag_id}</span>
                <br>
                <span class="text-xs text-gray-500">Tipo: ${tag.tag_type || 'N/A'}</span>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    ${getStatusClass(tag.status_color)}">
                    ${tag.status_text}
                </span>
            </td>
            <td class="px-4 py-3">
                ${tag.has_product ? `
                    <div class="text-sm font-medium text-gray-900">${truncate(tag.goods_name, 40)}</div>
                    <div class="text-xs text-gray-500">Código: ${tag.goods_code}</div>
                ` : `
                    <span class="text-gray-400 italic">Sin producto</span>
                `}
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                        <div class="h-2 rounded-full ${getBatteryClass(tag.power_percent)}"
                            style="width: ${tag.power_percent}%"></div>
                    </div>
                    <span class="text-xs text-gray-600">${tag.power_percent}%</span>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="text-sm">${tag.rssi || 'N/A'} dBm</span>
                <br>
                <span class="text-xs text-gray-500">${tag.signal_quality}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">
                ${tag.last_recv_time ? formatDate(tag.last_recv_time) : 'Sin datos'}
            </td>
            <td class="px-4 py-3">
                <button onclick="refreshTag('${tag.tag_id}')"
                    class="text-blue-600 hover:text-blue-800 transition" title="Refrescar etiqueta">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getStatusClass(color) {
    const classes = {
        'green': 'bg-green-100 text-green-800',
        'yellow': 'bg-yellow-100 text-yellow-800',
        'red': 'bg-red-100 text-red-800',
        'blue': 'bg-blue-100 text-blue-800',
        'orange': 'bg-orange-100 text-orange-800',
        'gray': 'bg-gray-100 text-gray-800',
    };
    return classes[color] || classes['gray'];
}

function getBatteryClass(percent) {
    if (percent >= 60) return 'bg-green-500';
    if (percent >= 30) return 'bg-yellow-500';
    return 'bg-red-500';
}

function truncate(str, length) {
    if (!str) return '';
    return str.length > length ? str.substring(0, length) + '...' : str;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll').checked;
    document.querySelectorAll('.tag-checkbox').forEach(cb => {
        cb.checked = selectAll;
    });
    updateSelection();
}

function updateSelection() {
    selectedTags = Array.from(document.querySelectorAll('.tag-checkbox:checked'))
        .map(cb => cb.dataset.tagId);

    const btn = document.getElementById('refreshSelectedBtn');
    btn.disabled = selectedTags.length === 0;

    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.tag-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.tag-checkbox:checked');
    document.getElementById('selectAll').checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
}

function refreshTag(tagId) {
    if (!confirm('¿Deseas refrescar esta etiqueta?')) return;

    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`{{ url('tags') }}/${tagId}/refresh`, {
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

function refreshSelected() {
    if (selectedTags.length === 0) return;

    document.getElementById('refreshModalText').textContent =
        `¿Deseas actualizar ${selectedTags.length} etiqueta(s) seleccionada(s)?`;
    document.getElementById('refreshModal').classList.remove('hidden');
    document.getElementById('refreshModal').classList.add('flex');
}

function closeRefreshModal() {
    document.getElementById('refreshModal').classList.add('hidden');
    document.getElementById('refreshModal').classList.remove('flex');
}

function confirmRefresh() {
    closeRefreshModal();

    const btn = document.getElementById('refreshSelectedBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
    btn.disabled = true;

    fetch('{{ route('tags.refresh.multiple') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ tag_ids: selectedTags })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Deseleccionar todo
            document.getElementById('selectAll').checked = false;
            document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = false);
            updateSelection();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error de conexión');
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = selectedTags.length === 0;
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
