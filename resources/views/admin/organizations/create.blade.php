@extends('admin.layouts.admin')

@section('title', 'Nueva Organización')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.organizations.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Nueva Organización</h1>
</div>

<form action="{{ route('admin.organizations.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- Datos básicos -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Datos básicos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug (URL) <span class="text-red-500">*</span></label>
                <input type="text" name="slug" value="{{ old('slug') }}"
                       placeholder="mi-empresa"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
                <p class="text-xs text-gray-400 mt-1">Solo minúsculas, números y guiones</p>
                @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <!-- Credenciales API eRetail -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            <i class="fas fa-plug text-blue-500 mr-2"></i>Servidor eRetail (API)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL base</label>
                <input type="url" name="eretail_base_url" value="{{ old('eretail_base_url') }}"
                       placeholder="http://servidor:5003"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('eretail_base_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shop Code por defecto</label>
                <input type="text" name="eretail_default_shop_code" value="{{ old('eretail_default_shop_code') }}"
                       placeholder="0001"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario API</label>
                <input type="text" name="eretail_username" value="{{ old('eretail_username') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña API</label>
                <input type="password" name="eretail_password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Credenciales DB directa eRetail -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            <i class="fas fa-database text-purple-500 mr-2"></i>Base de datos eRetail (acceso directo)
        </h2>
        <p class="text-sm text-gray-500 mb-4">Requerido para Dashboard y gestión de etiquetas.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                <input type="text" name="eretail_db_host" value="{{ old('eretail_db_host') }}"
                       placeholder="192.168.1.100"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Puerto</label>
                <input type="number" name="eretail_db_port" value="{{ old('eretail_db_port', '3306') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base de datos</label>
                <input type="text" name="eretail_db_database" value="{{ old('eretail_db_database') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario DB</label>
                <input type="text" name="eretail_db_username" value="{{ old('eretail_db_username') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña DB</label>
                <input type="password" name="eretail_db_password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-3">
        <a href="{{ route('admin.organizations.index') }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-save mr-2"></i>Crear Organización
        </button>
    </div>
</form>
@endsection
