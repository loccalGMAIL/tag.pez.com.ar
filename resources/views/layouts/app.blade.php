<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ESL Retail Updater')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex flex-col justify-center">
                        <a href="{{ route('dashboard.index') }}" class="text-xl font-bold text-gray-800 hover:text-blue-600 transition duration-300">
                            <i class="fas fa-tags text-blue-600 mr-2"></i>
                            ESL Retail Updater
                        </a>
                        <span style="font-family: Arial, sans-serif; font-size: 10px; color: #9ca3af; margin-left: 28px;">
                            v{{ config('version.major') }}.{{ config('version.minor') }}.{{ config('version.patch') }}
                        </span>
                    </div>

                    <!-- Navigation Links (desktop) -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('dashboard.index') }}"
                           class="@if(request()->routeIs('dashboard.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-line mr-2"></i>
                            Dashboard
                        </a>

                        <a href="{{ route('uploads.index') }}"
                           class="@if(request()->routeIs('uploads.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-file-upload mr-2"></i>
                            Uploads
                        </a>

                        <a href="{{ route('tags.index') }}"
                           class="@if(request()->routeIs('tags.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tag mr-2"></i>
                            Etiquetas
                        </a>
                    </div>
                </div>

                <!-- Right side (desktop) -->
                <div class="hidden sm:flex items-center space-x-4">
                    <!-- Usuario logueado -->
                    <div class="flex items-center text-gray-600 text-sm">
                        <div class="flex items-center mr-4">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                <i class="fas fa-user text-blue-600 text-xs"></i>
                            </div>
                            <span class="font-medium hidden md:inline">{{ auth()->user()->name }}</span>
                        </div>
                    </div>

                    {{-- Usuarios --}}
                    <a href="{{ route('users.index') }}"
                       class="@if(request()->routeIs('users.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-users mr-2"></i>
                    </a>

                    {{-- Configuración --}}
                    <a href="{{ route('settings.index') }}"
                       class="@if(request()->routeIs('settings.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-cog mr-2"></i>
                    </a>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="text-gray-500 hover:text-red-600 transition duration-300 p-2 rounded-lg hover:bg-red-50"
                                title="Cerrar Sesión"
                                onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>

                    <!-- Separator -->
                    <div class="border-l border-gray-300 h-6"></div>

                    <!-- Nuevo Upload Button -->
                    <a href="{{ route('uploads.create') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>
                        Nuevo Upload
                    </a>
                </div>

                <!-- Hamburger button (mobile only) -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = !open"
                            class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Menú">
                        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="open" x-transition class="sm:hidden border-t border-gray-200 bg-white">
            <!-- Nav links -->
            <div class="pt-2 pb-3 space-y-1 px-4">
                <a href="{{ route('dashboard.index') }}"
                   class="@if(request()->routeIs('dashboard.*')) bg-blue-50 border-blue-500 text-blue-700 @else border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800 @endif flex items-center px-3 py-2 border-l-4 text-sm font-medium rounded-r-md">
                    <i class="fas fa-chart-line mr-3 w-4"></i>
                    Dashboard
                </a>
                <a href="{{ route('uploads.index') }}"
                   class="@if(request()->routeIs('uploads.*')) bg-blue-50 border-blue-500 text-blue-700 @else border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800 @endif flex items-center px-3 py-2 border-l-4 text-sm font-medium rounded-r-md">
                    <i class="fas fa-file-upload mr-3 w-4"></i>
                    Uploads
                </a>
                <a href="{{ route('tags.index') }}"
                   class="@if(request()->routeIs('tags.*')) bg-blue-50 border-blue-500 text-blue-700 @else border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800 @endif flex items-center px-3 py-2 border-l-4 text-sm font-medium rounded-r-md">
                    <i class="fas fa-tag mr-3 w-4"></i>
                    Etiquetas
                </a>
            </div>
            <!-- User info + actions -->
            <div class="border-t border-gray-200 pt-3 pb-4 px-4 space-y-3">
                <div class="flex items-center text-gray-700 text-sm">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user text-blue-600 text-xs"></i>
                    </div>
                    <span class="font-medium">{{ auth()->user()->name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('users.index') }}"
                       class="@if(request()->routeIs('users.*')) text-blue-600 @else text-gray-500 hover:text-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-50">
                        <i class="fas fa-users mr-2"></i>
                        Usuarios
                    </a>
                    <a href="{{ route('settings.index') }}"
                       class="@if(request()->routeIs('settings.*')) text-blue-600 @else text-gray-500 hover:text-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-50">
                        <i class="fas fa-cog mr-2"></i>
                        Configuración
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="flex items-center px-3 py-2 text-sm font-medium text-red-500 hover:text-red-700 rounded-md hover:bg-red-50"
                                onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Salir
                        </button>
                    </form>
                </div>
                <a href="{{ route('uploads.create') }}"
                   class="w-full flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Upload
                </a>
            </div>
        </div>
    </nav>

    <!-- Banner impersonación (solo visible para super-admin) -->
    @if(auth()->check() && auth()->user()->is_super_admin && session('impersonating_org'))
    <div class="bg-purple-700 text-white text-sm py-2 px-4 flex items-center justify-between">
        <span>
            <i class="fas fa-eye mr-2"></i>
            Visualizando como: <strong>{{ session('impersonating_org_name', 'Organización') }}</strong>
        </span>
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.organizations.index') }}" class="underline hover:no-underline text-purple-200">
                <i class="fas fa-arrow-left mr-1"></i> Volver al panel admin
            </a>
            <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="inline">
                @csrf
                <button type="submit" class="bg-purple-900 hover:bg-purple-800 px-3 py-1 rounded text-xs">
                    <i class="fas fa-times mr-1"></i> Salir de la vista
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Page Content -->
    <main class="py-8">
        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>