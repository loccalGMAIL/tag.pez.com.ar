@extends('admin.layouts.admin')

@section('title', 'Organizaciones')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-building text-blue-500 mr-2"></i>
        Organizaciones
    </h1>
    <a href="{{ route('admin.organizations.create') }}"
       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i> Nueva Organización
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organización</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">eRetail URL</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuarios</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creada</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($organizations as $org)
            <tr class="@if($org->trashed()) opacity-50 @endif hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900">{{ $org->name }}</div>
                    <div class="text-xs text-gray-500">{{ $org->slug }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $org->eretail_base_url ? Str::limit($org->eretail_base_url, 40) : '—' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                    {{ $org->users_count }}
                </td>
                <td class="px-6 py-4">
                    @if($org->trashed())
                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Eliminada</span>
                    @elseif(!$org->is_active)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Inactiva</span>
                    @else
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Activa</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $org->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('admin.organizations.show', $org) }}"
                       class="text-blue-600 hover:underline text-sm">Ver</a>
                    <a href="{{ route('admin.organizations.edit', $org) }}"
                       class="text-gray-600 hover:underline text-sm">Editar</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                    No hay organizaciones registradas.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
