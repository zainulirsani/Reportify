{{-- Navbar --}}
<header class="bg-white shadow-sm p-4 flex justify-between items-center">
    <h2 class="text-xl font-semibold text-gray-800">
        {{ $header ?? 'Dashboard' }}
    </h2>

    <div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); this.closest('form').submit();"
               class="text-gray-500 hover:text-gray-700">
                Log Out
            </a>
        </form>
    </div>
</header>