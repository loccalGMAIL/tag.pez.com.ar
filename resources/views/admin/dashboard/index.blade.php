@extends('admin.layouts.admin')

@section('title', 'Panel Admin')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-shield-alt text-yellow-500 mr-2"></i>
        Panel de Super Administración
    </h1>
</div>

<!-- Métricas globales -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Organizaciones</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalOrganizations }}</p>
                <p class="text-xs text-green-600 mt-1">{{ $activeOrganizations }} activas</p>
            </div>
            <i class="fas fa-building text-blue-500 text-3xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Usuarios</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalUsers }}</p>
                <p class="text-xs text-gray-400 mt-1">Todos los tenants</p>
            </div>
            <i class="fas fa-users text-green-500 text-3xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Uploads totales</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalUploads }}</p>
                <p class="text-xs text-gray-400 mt-1">Todos los tenants</p>
            </div>
            <i class="fas fa-file-upload text-purple-500 text-3xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Acciones</p>
                <a href="{{ route('admin.organizations.create') }}"
                   class="mt-2 inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-1"></i> Nueva Org.
                </a>
            </div>
            <i class="fas fa-cog text-gray-400 text-3xl"></i>
        </div>
    </div>
</div>

<!-- Últimas organizaciones -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">Últimas organizaciones</h2>
        <a href="{{ route('admin.organizations.index') }}" class="text-blue-600 text-sm hover:underline">Ver todas</a>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($recentOrganizations as $org)
        <div class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center
                    @if($org->is_active && !$org->trashed()) bg-green-100 text-green-600
                    @else bg-red-100 text-red-600 @endif">
                    <i class="fas fa-building text-sm"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $org->name }}</p>
                    <p class="text-xs text-gray-500">{{ $org->slug }} · {{ $org->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                @if($org->trashed())
                    <span class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded">Eliminada</span>
                @elseif(!$org->is_active)
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-600 text-xs rounded">Inactiva</span>
                @else
                    <span class="px-2 py-1 bg-green-100 text-green-600 text-xs rounded">Activa</span>
                @endif
                <a href="{{ route('admin.organizations.show', $org) }}"
                   class="text-blue-600 hover:underline text-sm">Ver</a>
            </div>
        </div>
        @empty
        <div class="px-6 py-8 text-center text-gray-400">
            No hay organizaciones aún.
        </div>
        @endforelse
    </div>
</div>
@endsection
