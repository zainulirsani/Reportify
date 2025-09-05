{{-- Sidebar --}}
<aside class="w-64 flex-shrink-0 bg-gray-800 text-gray-100 h-screen flex flex-col">
    <div class="h-16 flex items-center justify-center border-b border-gray-700">
        <h1 class="text-2xl font-bold text-white">LaraLog</h1>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-gray-200 hover:bg-gray-700 rounded-md transition duration-200">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            Dashboard
        </a>
        <a href="{{ route('systems') }}" class="flex items-center px-4 py-2 text-gray-200 hover:bg-gray-700 rounded-md transition duration-200">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.87a3 3 0 01-.879-2.122v-1.007M5.25 6.002a4.5 4.5 0 019 0v6a4.5 4.5 0 01-9 0v-6z" />
            </svg>
            Management Sistem
        </a>
        <a href="{{ route('reports') }}" class="flex items-center px-4 py-2 text-gray-200 hover:bg-gray-700 rounded-md transition duration-200">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.87a3 3 0 01-.879-2.122v-1.007M5.25 6.002a4.5 4.5 0 019 0v6a4.5 4.5 0 01-9 0v-6z" />
            </svg>
            Management Report
        </a>
    </nav>

    <div class="p-4 border-t border-gray-700">
        <p class="font-semibold">{{ Auth::user()->name }}</p>
        <p class="text-sm text-gray-400">{{ Auth::user()->email }}</p>
    </div>
</aside>