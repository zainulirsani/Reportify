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
        <div x-data="{
            system_id: '{{ request('system_id', '') }}',
            status: '{{ request('status', '') }}',
            work_type: '{{ request('work_type', '') }}',
            date_filter: '{{ request('date_filter', '') }}',
            start_date: '{{ request('start_date', '') }}',
            end_date: '{{ request('end_date', '') }}',
            exportUrl() {
                const params = new URLSearchParams();
                if (this.system_id) params.append('system_id', this.system_id);
                if (this.status) params.append('status', this.status);
                if (this.work_type) params.append('work_type', this.work_type);
                if (this.date_filter) {
                    params.append('date_filter', this.date_filter);
                    if (this.date_filter === 'custom') {
                        if (this.start_date) params.append('start_date', this.start_date);
                        if (this.end_date) params.append('end_date', this.end_date);
                    }
                }
                return `{{ route('reports.export') }}?${params.toString()}`;
            }
        }" class="bg-white p-6 rounded-xl border border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Filter Laporan</h3>
            <form action="{{ route('reports') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Filter Proyek/Sistem --}}
                    <div>
                        <label for="system_id" class="block text-sm font-medium text-slate-700">Proyek</label>
                        <select name="system_id" id="system_id" x-model="system_id"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Semua Proyek</option>
                            @foreach ($systems as $system)
                                <option value="{{ $system->id }}">{{ $system->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Filter Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" id="status" x-model="status"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Semua Status</option>
                            <option value="completed">Completed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    {{-- FILTER BARU: Jenis Pekerjaan --}}
                    <div>
                        <label for="work_type" class="block text-sm font-medium text-slate-700">Jenis Pekerjaan</label>
                        <select name="work_type" id="work_type" x-model="work_type"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Semua Jenis</option>
                            <option value="normal">Kerja Normal</option>
                            <option value="overtime">Lembur</option>
                        </select>
                    </div>
                    {{-- Filter Tanggal (Pre-defined) --}}
                    <div>
                        <label for="date_filter" class="block text-sm font-medium text-slate-700">Periode Waktu</label>
                        <select name="date_filter" id="date_filter" x-model="date_filter"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Semua Waktu</option>
                            <option value="week">Minggu Ini</option>
                            <option value="month">Bulan Ini</option>
                            <option value="year">Tahun Ini</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>

                {{-- Filter Tanggal (Custom Range) --}}
                <div x-show="date_filter === 'custom'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" x-model="start_date"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-slate-700">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 mt-4 pt-4 border-t">
                    <a :href="exportUrl()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold text-sm flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span>Export</span>
                    </a>
                    <a href="{{ route('reports') }}"
                        class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold text-sm">Reset</a>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm">Filter</button>
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
                                        <input type="text" name="title" id="title"
                                            x-model="reportData.title"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    </div>
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-slate-700">Deskripsi (Hasil Generate
                                            AI)</label>
                                        <textarea name="description" id="description" rows="10" x-model="reportData.description"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"></textarea>
                                    </div>
                                    <template
                                        x-if="reportData && reportData.code_snippets && reportData.code_snippets.length > 0">
                                        <div class="space-y-4">
                                            <label class="block text-sm font-medium text-slate-700">Potongan Kode
                                                Penting</label>
                                            <template x-for="snippet in reportData.code_snippets"
                                                :key="snippet.id">
                                                <div class="border border-slate-200 rounded-lg">
                                                    <p class="text-xs text-slate-500 bg-slate-50 px-4 py-2 border-b border-slate-200"
                                                        x-text="snippet.description"></p>
                                                    <pre class="text-xs bg-slate-900 text-white p-4 overflow-x-auto"><code x-text="snippet.content"></code></pre>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- TAMPILKAN FULL DIFF (OPSIONAL, UNTUK REFERENSI) --}}
                                    <template x-if="reportData && reportData.raw_diff">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Bukti Perubahan
                                                Kode (Full Diff)</label>
                                            <pre class="mt-1 block w-full text-xs bg-slate-900 text-white p-4 rounded-md overflow-x-auto max-h-64"><code x-text="reportData.raw_diff"></code></pre>
                                        </div>
                                    </template>
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
                                    <div>
                                        <label for="work_type-modal"
                                            class="block text-sm font-medium text-slate-700">Jenis Pekerjaan</label>
                                        <select name="work_type" id="work_type-modal" x-model="reportData.work_type"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                            <option value="normal">Kerja Normal</option>
                                            <option value="overtime">Lembur</option>
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
