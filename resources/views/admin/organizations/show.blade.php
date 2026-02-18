@extends('admin.layouts.admin')

@section('title', $organization->name)

@section('content')
<div class="flex justify-between items-start mb-6">
    <div>
        <a href="{{ route('admin.organizations.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2 flex items-center space-x-3">
            <span>{{ $organization->name }}</span>
            @if(!$organization->is_active || $organization->trashed())
                <span class="px-2 py-1 bg-red-100 text-red-600 text-sm rounded-full">Inactiva</span>
            @else
                <span class="px-2 py-1 bg-green-100 text-green-600 text-sm rounded-full">Activa</span>
            @endif
        </h1>
        <p class="text-gray-500 text-sm">Slug: <code class="bg-gray-100 px-1 rounded">{{ $organization->slug }}</code></p>
    </div>
    <div class="flex space-x-2">
        <!-- Impersonate -->
        @if($organization->is_active && !$organization->trashed())
        <form method="POST" action="{{ route('admin.organizations.impersonate', $organization) }}">
            @csrf
            <button type="submit" class="px-3 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">
                <i class="fas fa-eye mr-1"></i> Ver como cliente
            </button>
        </form>
        @endif

        <!-- Activar/Desactivar -->
        <form method="POST" action="{{ route('admin.organizations.activate', $organization) }}">
            @csrf
            <button type="submit" class="px-3 py-2 {{ $organization->is_active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-600 hover:bg-green-700' }} text-white text-sm rounded-lg">
                <i class="fas {{ $organization->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                {{ $organization->is_active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>

        <a href="{{ route('admin.organizations.edit', $organization) }}"
           class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
            <i class="fas fa-edit mr-1"></i> Editar
        </a>
    </div>
</div>

<!-- Métricas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500">Usuarios</p>
        <p class="text-2xl font-bold text-gray-800">{{ $organization->users->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500">Uploads totales</p>
        <p class="text-2xl font-bold text-gray-800">{{ $uploadsCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500">Servidor eRetail</p>
        <p class="text-sm font-medium text-gray-700 mt-1 truncate">
            {{ $organization->eretail_base_url ?? '—' }}
        </p>
    </div>
</div>

<!-- Usuarios de la organización -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">Usuarios</h2>
        <a href="{{ route('admin.users.index') }}?org={{ $organization->id }}"
           class="text-blue-600 text-sm hover:underline">Gestionar usuarios</a>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($organization->users as $user)
        <div class="px-6 py-3 flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-800">{{ $user->name }}</p>
                <p class="text-xs text-gray-500">{{ $user->email }}</p>
            </div>
            <span class="text-xs text-gray-400">Desde {{ $user->created_at->format('d/m/Y') }}</span>
        </div>
        @empty
        <div class="px-6 py-6 text-center text-gray-400 text-sm">
            Sin usuarios asignados.
        </div>
        @endforelse
    </div>
</div>

<!-- Configuración eRetail -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">Configuración eRetail</h2>
        <button onclick="testDbConnection()"
                class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 flex items-center gap-2">
            <i class="fas fa-database"></i> Probar conexión BD
        </button>
    </div>

    <!-- Resultado del test -->
    <div id="db-test-result" class="hidden px-6 pt-4"></div>

    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-gray-500">URL API</p>
            <p class="font-medium">{{ $organization->eretail_base_url ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Usuario API</p>
            <p class="font-medium">{{ $organization->eretail_username ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Shop Code</p>
            <p class="font-medium">{{ $organization->eretail_default_shop_code ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">DB Host</p>
            <p class="font-medium">{{ $organization->eretail_db_host ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">DB Database</p>
            <p class="font-medium">{{ $organization->eretail_db_database ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">DB Usuario</p>
            <p class="font-medium">{{ $organization->eretail_db_username ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Contraseña API</p>
            <p class="font-medium text-gray-400">{{ $organization->eretail_password ? '••••••••' : '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Contraseña BD</p>
            <p class="font-medium text-gray-400">{{ $organization->eretail_db_password ? '••••••••' : '—' }}</p>
        </div>
    </div>
</div>

<script>
function testDbConnection() {
    const resultEl = document.getElementById('db-test-result');
    resultEl.classList.remove('hidden');
    resultEl.innerHTML = '<p class="text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-1"></i>Probando conexión…</p>';

    fetch('{{ route('admin.organizations.test-db', $organization) }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            resultEl.innerHTML = `
                <div class="bg-green-50 border border-green-300 rounded-lg p-3 mb-4 text-sm">
                    <p class="font-semibold text-green-700"><i class="fas fa-check-circle mr-1"></i>${data.message}</p>
                    <p class="text-green-600 mt-1">Host: ${data.details.host} · DB: ${data.details.database} · MySQL ${data.details.mysql_version}</p>
                </div>`;
        } else {
            resultEl.innerHTML = `
                <div class="bg-red-50 border border-red-300 rounded-lg p-3 mb-4 text-sm">
                    <p class="font-semibold text-red-700"><i class="fas fa-times-circle mr-1"></i>Error de conexión</p>
                    <p class="text-red-600 mt-1 break-all">${data.message}</p>
                    <p class="text-gray-500 mt-1 text-xs">Host: ${data.details.host} · DB: ${data.details.database}</p>
                </div>`;
        }
    })
    .catch(() => {
        resultEl.innerHTML = '<div class="bg-red-50 border border-red-300 rounded-lg p-3 mb-4 text-sm text-red-700">Error de red al probar la conexión.</div>';
    });
}
</script>
@endsection
