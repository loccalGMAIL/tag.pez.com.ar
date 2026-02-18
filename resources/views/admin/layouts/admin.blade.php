<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ESL SaaS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar Admin -->
    <nav class="bg-gray-900 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('admin.dashboard') }}" class="text-white font-bold text-lg flex items-center">
                        <i class="fas fa-shield-alt text-yellow-400 mr-2"></i>
                        Super Admin
                    </a>

                    <a href="{{ route('admin.organizations.index') }}"
                       class="@if(request()->routeIs('admin.organizations.*')) text-yellow-400 @else text-gray-300 hover:text-white @endif text-sm font-medium">
                        <i class="fas fa-building mr-1"></i> Organizaciones
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                       class="@if(request()->routeIs('admin.users.*')) text-yellow-400 @else text-gray-300 hover:text-white @endif text-sm font-medium">
                        <i class="fas fa-users mr-1"></i> Usuarios
                    </a>

                    <a href="{{ route('admin.uploads.index') }}"
                       class="@if(request()->routeIs('admin.uploads.*')) text-yellow-400 @else text-gray-300 hover:text-white @endif text-sm font-medium">
                        <i class="fas fa-file-upload mr-1"></i> Uploads
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="text-gray-400 text-sm">
                        <i class="fas fa-user-shield mr-1 text-yellow-400"></i>
                        {{ Auth::user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-300 hover:text-white text-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i> Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
