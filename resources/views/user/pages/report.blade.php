<x-app-layout>
    <x-slot name="header">
        {{ __('Daftar Laporan Pekerjaan') }}
    </x-slot>

    <div x-data="{
        showModal: false,
        isLoading: false,
        reportData: null,
        formUrl: '',
        openReportModal(reportId) {
            this.showModal = true;
            this.isLoading = true;
            this.reportData = null; // Kosongkan data lama
    
            fetch(`/reports/${reportId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    this.reportData = data;
                    this.formUrl = `/reports/${data.id}`;
                    this.isLoading = false;
                })
                .catch(error => {
                    console.error('Error fetching report details:', error);
                    alert('Gagal memuat detail laporan. Silakan coba lagi.');
                    this.showModal = false;
                    this.isLoading = false;
                });
        }
    }" class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Filter Laporan</h3>
            <form action="{{ route('reports') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Filter by System --}}
                    <div>
                        <label for="system_id" class="block text-sm font-medium text-slate-700">Proyek / Sistem</label>
                        <select name="system_id" id="system_id"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Semua Proyek</option>
                            @foreach ($systems as $system)
                                <option value="{{ $system->id }}"
                                    {{ request('system_id') == $system->id ? 'selected' : '' }}>
                                    {{ $system->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter by Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" id="status"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                            </option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In
                                Progress</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                        </select>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex items-end space-x-2">
                        <button type="submit"
                            class="w-full justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">Filter</button>
                        <a href="{{ route('reports') }}"
                            class="w-full text-center px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Proyek
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Judul
                                Task</th>
                            <th class="px-6 py-3 text-center font-medium text-slate-500 uppercase tracking-wider">Status
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Tanggal
                                Selesai</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($reports as $report)
                            <tr class="hover:bg-slate-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-800">
                                    {{ $report->system->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ Str::limit($report->title, 40) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php
                                        $statusClass = match ($report->status) {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-slate-100 text-slate-800',
                                        };
                                    @endphp
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                    {{ $report->completed_at ? $report->completed_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{-- PERUBAHAN DI SINI: Tombol memanggil fungsi Alpine.js --}}
                                    <button @click="openReportModal({{ $report->id }})"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium">Lihat Detail</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-500">
                                    <p class="font-semibold">Tidak ada laporan yang cocok dengan filter Anda.</p>
                                    <p class="text-xs mt-1">Coba reset filter untuk melihat semua laporan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{-- appends(request()->query()) penting agar filter tetap aktif saat pindah halaman --}}
                    {{ $reports->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
        <div x-show="showModal" style="display: none;" x-cloak class="relative z-50">
            {{-- Backdrop --}}
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50"></div>

            {{-- Konten Modal --}}
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="showModal" x-transition @click.away="showModal = false"
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all">

                        {{-- Loading State --}}
                        <div x-show="isLoading" class="p-12 text-center">
                            <p class="text-slate-500">Memuat data laporan...</p>
                        </div>

                        {{-- Form Content --}}
                        <template x-if="reportData">
                            <form :action="formUrl" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                                    <h3 class="text-lg font-semibold text-slate-800">Detail Laporan</h3>
                                </div>

                                <div class="p-6 space-y-4">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-slate-700">Judul
                                            Task</label>
                                        <input type="text" name="title" id="title" x-model="reportData.title"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    </div>
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-slate-700">Deskripsi (Hasil Generate
                                            AI)</label>
                                        <textarea name="description" id="description" rows="10" x-model="reportData.description"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"></textarea>
                                    </div>
                                    <div>
                                        <label for="status-modal"
                                            class="block text-sm font-medium text-slate-700">Status</label>
                                        <select name="status" id="status-modal" x-model="reportData.status"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                            <option value="pending">Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-slate-50 px-6 py-4 flex justify-end space-x-3">
                                    <button @click.prevent="showModal = false" type="button"
                                        class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Batal</button>
                                    <button type="submit"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Simpan
                                        Perubahan</button>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
