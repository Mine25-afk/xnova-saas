<nav class="bg-white border-b border-gray-100" aria-label="Navegacion de cuenta">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-slate-800 no-underline">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-600 text-lg font-bold text-white">x</span>
                    <span class="text-lg font-semibold">xNova</span>
                </a>

                <div class="hidden sm:flex sm:items-center sm:gap-6">
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-600' }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                        Panel
                    </a>
                    <a href="{{ route('profile.edit') }}" class="text-sm font-medium {{ request()->routeIs('profile.*') ? 'text-blue-600' : 'text-slate-600' }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        Perfil
                    </a>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <div class="text-right">
                    <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-slate-500">{{ Auth::user()->email }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cerrar sesion
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 sm:hidden">
        <div class="space-y-1 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : 'text-slate-600' }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                Panel
            </a>
            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-600' }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                Perfil
            </a>

            <form method="POST" action="{{ route('logout') }}" class="pt-2">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-sm font-medium text-slate-700">
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>
</nav>
