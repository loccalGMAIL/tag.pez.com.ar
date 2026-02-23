@extends('admin.layouts.admin')

@section('title', 'Usuarios')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-users text-green-500 mr-2"></i>
        Usuarios (todos los tenants)
    </h1>
    <button onclick="openCreateModal()"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i> Nuevo Usuario
    </button>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organización</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                    @if($user->organization)
                        <a href="{{ route('admin.organizations.show', $user->organization) }}"
                           class="text-blue-600 hover:underline">{{ $user->organization->name }}</a>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if($user->is_super_admin)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Super Admin</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">Usuario</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4 text-right space-x-3">
                    <button onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->organization_id }}', {{ $user->is_super_admin ? 'true' : 'false' }})"
                            class="text-blue-600 hover:underline text-sm">
                        Editar
                    </button>
                    @if(auth()->id() !== $user->id)
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                          class="inline"
                          onsubmit="return confirm('¿Eliminar usuario {{ addslashes($user->name) }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-sm">Eliminar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No hay usuarios.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal crear usuario -->
<div id="modal-create" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Nuevo Usuario</h2>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" name="name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Organización</label>
                <select name="organization_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">— Ninguna (super admin) —</option>
                    @foreach($organizations as $org)
                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_super_admin" value="1" id="create_is_super_admin">
                <label for="create_is_super_admin" class="text-sm text-gray-700">Super Administrador</label>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Crear
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal editar usuario -->
<div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Editar Usuario</h2>
        <form id="form-edit" method="POST" action="" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Organización</label>
                <select name="organization_id" id="edit_organization_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">— Ninguna (super admin) —</option>
                    @foreach($organizations as $org)
                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_super_admin" value="1" id="edit_is_super_admin">
                <label for="edit_is_super_admin" class="text-sm text-gray-700">Super Administrador</label>
            </div>
            <div class="border-t border-gray-200 pt-3">
                <p class="text-xs text-gray-400 mb-2">Dejar en blanco para no cambiar la contraseña</p>
                <div class="space-y-2">
                    <input type="password" name="password" placeholder="Nueva contraseña (mín. 6 caracteres)" minlength="6"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="password" name="password_confirmation" placeholder="Confirmar nueva contraseña"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const baseUpdateUrl = "{{ url('admin/users') }}";

function openCreateModal() {
    document.getElementById('modal-create').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('modal-create').classList.add('hidden');
}

function openEditModal(id, name, email, orgId, isSuperAdmin) {
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_organization_id').value = orgId || '';
    document.getElementById('edit_is_super_admin').checked = isSuperAdmin;

    // Limpiar campos de contraseña
    document.querySelectorAll('#modal-edit input[type="password"]').forEach(el => el.value = '');

    // Actualizar el action del form
    document.getElementById('form-edit').action = baseUpdateUrl + '/' + id;

    document.getElementById('modal-edit').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('modal-edit').classList.add('hidden');
}

// Cerrar modales al hacer click fuera
['modal-create', 'modal-edit'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
@endsection
