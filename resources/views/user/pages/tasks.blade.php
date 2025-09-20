<x-app-layout>
    <x-slot name="header">
        {{ __('Daftar Tugas') }}
    </x-slot>

    <div x-data="{
        // State for edit/create slide-over panel
        showPanel: false,
        isEditMode: false,
        isRewriting: false,
        formTitle: '',
        formUrl: '',
        taskData: { id: null, title: '', description: '', system_id: '', status: 'todo', attachment_path: null },
        rewriteSuggestions: [],
    
        // State for view detail modal
        showDetailModal: false,
        isLoadingDetail: false,
        detailTaskData: null,
    
        openCreatePanel() {
            this.isEditMode = false;
            this.formTitle = 'Buat Tugas Baru';
            this.formUrl = '{{ route('tasks.store') }}';
            this.taskData = { id: null, title: '', description: '', system_id: '{{ $systems->first()->id ?? '' }}', status: 'todo', attachment_path: null };
            this.rewriteSuggestions = [];
            this.showPanel = true;
        },
        openEditPanel(task) {
            this.isEditMode = true;
            this.formTitle = 'Edit Tugas';
            this.formUrl = `/tasks/${task.id}`;
            this.taskData = {
                id: task.id,
                title: task.title,
                description: task.description || '',
                system_id: task.system_id,
                status: task.status,
                attachment_path: task.attachment_path
            };
            this.rewriteSuggestions = [];
            this.showPanel = true;
        },
        rewriteDescription() {
            if (!this.taskData.description) {
                alert('Silakan isi deskripsi teknis terlebih dahulu.');
                return;
            }
            this.isRewriting = true;
            this.rewriteSuggestions = [];
    
            fetch('{{ route('ai.rewrite') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ text: this.taskData.description })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.suggestions && data.suggestions.length > 0) {
                        this.rewriteSuggestions = data.suggestions;
                    } else {
                        alert('AI tidak dapat memberikan saran saat ini.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan saat menghubungi AI.');
                })
                .finally(() => this.isRewriting = false);
        },
        selectSuggestion(suggestion) {
            this.taskData.description = suggestion;
            this.rewriteSuggestions = [];
        },
        openDetailModal(taskId) {
            this.showDetailModal = true;
            this.isLoadingDetail = true;
            this.detailTaskData = null;
    
            fetch(`/tasks/${taskId}`)
                .then(res => {
                    if (!res.ok) throw new Error('Gagal memuat data');
                    return res.json();
                })
                .then(data => {
                    this.detailTaskData = data;
                    this.isLoadingDetail = false;
                })
                .catch(error => {
                    console.error(error);
                    alert('Terjadi kesalahan saat mengambil detail tugas.');
                    this.showDetailModal = false;
                    this.isLoadingDetail = false;
                });
        }
    }" class="space-y-6">

        {{-- KONTEN UTAMA HALAMAN (Tombol & Tabel) --}}
        <div class="flex justify-between items-center">
            <p class="text-slate-500">Catat dan kelola semua pekerjaan yang perlu Anda selesaikan.</p>
            <button @click="openCreatePanel()"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold">
                + Tambah Tugas
            </button>
        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Kode
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Judul
                                Tugas</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Proyek
                            </th>
                            <th class="px-6 py-3 text-center font-medium text-slate-500 uppercase tracking-wider">Status
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-500">{{ $task->task_code }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ Str::limit($task->title, 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $task->system->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span @class([
                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        'bg-blue-100 text-blue-800' => $task->status === 'todo',
                                        'bg-yellow-100 text-yellow-800' => $task->status === 'in_progress',
                                        'bg-green-100 text-green-800' => $task->status === 'done',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button @click.prevent="openDetailModal({{ $task->id }})"
                                            class="p-2 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-colors duration-200"
                                            title="Lihat Detail">
                                            <span class="sr-only">Lihat Detail</span>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </button>
                                        <button @click="openEditPanel({{ json_encode($task) }})"
                                            class="p-2 rounded-full text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800 transition-colors duration-200"
                                            title="Edit Tugas">
                                            <span class="sr-only">Edit</span>
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-colors duration-200"
                                                title="Hapus Tugas">
                                                <span class="sr-only">Hapus</span>
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-500">Anda belum memiliki tugas.
                                    Silakan buat tugas baru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tasks->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>

        {{-- PANEL GESER UNTUK TAMBAH/EDIT TUGAS --}}
        <div x-show="showPanel" style="display: none;" class="relative z-50" x-cloak>
            <div x-show="showPanel" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                {{-- PERUBAHAN: Lebar panel diubah menjadi max-w-2xl --}}
                <div x-show="showPanel"
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-2xl">
                    <form :action="formUrl" method="POST" enctype="multipart/form-data"
                        class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        @csrf
                        <template x-if="isEditMode">
                            @method('PUT')
                        </template>
                        <div class="bg-indigo-700 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-white" x-text="formTitle"></h2>
                                <button @click="showPanel = false" type="button"
                                    class="rounded-md bg-indigo-700 text-indigo-200 hover:text-white"><span
                                        class="sr-only">Close panel</span><svg class="h-6 w-6"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg></button>
                            </div>
                        </div>
                        <div class="relative flex-1 px-4 py-6 sm:px-6 space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-slate-700">Judul
                                    Tugas</label>
                                <input type="text" name="title" id="title" x-model="taskData.title"
                                    required class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="system_id" class="block text-sm font-medium text-slate-700">Proyek /
                                    Sistem</label>
                                <select name="system_id" id="system_id" x-model="taskData.system_id" required
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm">
                                    <option value="" disabled>-- Pilih Sistem --</option>
                                    @foreach ($systems as $system)
                                        <option value="{{ $system->id }}">{{ $system->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="description"
                                    class="block text-sm font-medium text-slate-700">Deskripsi</label>
                                <div class="mt-1">
                                    <textarea name="description" id="description" rows="8" x-model="taskData.description"
                                        class="block w-full rounded-md border-slate-300 shadow-sm" placeholder="Tulis deskripsi teknis di sini..."></textarea>
                                </div>
                                <div class="mt-2 relative">
                                    <button @click.prevent="rewriteDescription()" :disabled="isRewriting"
                                        type="button"
                                        class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 disabled:text-slate-400 disabled:cursor-wait">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                            </path>
                                        </svg>
                                        <svg x-show="isRewriting" class="animate-spin -ml-1 mr-2 h-4 w-4"
                                            fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span
                                            x-text="isRewriting ? 'Memproses...' : 'Perbaiki Tulisan dengan AI'"></span>
                                    </button>
                                    <div x-show="rewriteSuggestions.length > 0" x-transition
                                        class="absolute z-10 mt-2 w-full bg-white rounded-md shadow-lg border border-slate-200">
                                        <ul class="max-h-60 overflow-auto py-1 text-base">
                                            <template x-for="(suggestion, index) in rewriteSuggestions"
                                                :key="index">
                                                <li @click="selectSuggestion(suggestion)"
                                                    class="text-slate-700 cursor-pointer select-none relative p-2 hover:bg-indigo-50">
                                                    <p class="block text-sm" x-text="suggestion"></p>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="attachment" class="block text-sm font-medium text-slate-700">Lampiran
                                    (Bukti Perintah)</label>
                                <input type="file" name="attachment" id="attachment"
                                    class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, PDF (Maks. 2MB).</p>
                                <template x-if="isEditMode && taskData.attachment_path">
                                    <div class="mt-2 text-sm">
                                        <a :href="`/storage/${taskData.attachment_path}`" target="_blank"
                                            class="text-indigo-600 hover:underline">Lihat Lampiran Saat Ini</a>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                                <select name="status" id="status" x-model="taskData.status" required :disabled="isEditMode && taskData.status === 'done'"
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm">
                                    <option value="todo">To-Do</option>
                                    <option value="in_progress">In Progress</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex-shrink-0 border-t border-gray-200 px-4 py-4 sm:px-6">
                            <div class="flex justify-end space-x-3">
                                <button @click="showPanel = false" type="button"
                                    class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Batal</button>
                                <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="showDetailModal" style="display: none;" x-cloak class="relative z-50">
            <div x-show="showDetailModal" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-60"></div>
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div @click.away="showDetailModal = false" x-show="showDetailModal" x-transition
                        class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all">

                        <div x-show="isLoadingDetail" class="p-12 text-center">
                            <p class="text-slate-500">Memuat data tugas...</p>
                        </div>

                        {{-- PERBAIKAN: Gunakan x-if untuk mencegah error saat data masih null --}}
                        <template x-if="detailTaskData">
                            <div class="max-h-[85vh] overflow-y-auto">
                                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 sticky top-0 z-10">
                                    <h3 class="text-lg font-semibold text-slate-800" x-text="detailTaskData.title">
                                    </h3>
                                    <p class="text-sm text-slate-500" x-text="`Kode: ${detailTaskData.task_code}`">
                                    </p>
                                </div>
                                <div class="p-6 space-y-6 text-sm">
                                    {{-- Detail Tugas Utama --}}
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <dt class="font-medium text-slate-500">Proyek</dt>
                                                <dd class="mt-1 text-slate-900"
                                                    x-text="detailTaskData.system ? detailTaskData.system.name : '-'">
                                                </dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-slate-500">Status</dt>
                                                <dd class="mt-1 text-slate-900"
                                                    x-text="detailTaskData.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())">
                                                </dd>
                                            </div>
                                            <div>
                                                {{--  <dt class="font-medium text-slate-500">Batas Waktu</dt>
                                            <dd class="mt-1 text-slate-900"
                                                x-text="detailTaskData.due_date ? new Date(detailTaskData.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'">
                                            </dd>  --}}
                                            </div>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-500">Deskripsi</dt>
                                            <dd class="mt-1 text-slate-700 whitespace-pre-wrap"
                                                x-text="detailTaskData.description || 'Tidak ada deskripsi.'"></dd>
                                        </div>

                                        <template x-if="detailTaskData.attachment_path">
                                            <div>
                                                <dt class="font-medium text-slate-500">Lampiran</dt>
                                                <dd class="mt-1">
                                                    <a :href="`/storage/${detailTaskData.attachment_path}`"
                                                        target="_blank" class="text-indigo-600 hover:underline">Lihat
                                                        Lampiran</a>
                                                </dd>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- PEMISAH --}}
                                    <div class="border-t border-slate-200"></div>

                                    {{-- Bukti Pengerjaan (Laporan Commit) --}}
                                    <div>
                                        <h4 class="text-base font-semibold text-slate-800 mb-3">Bukti Pengerjaan
                                            (Laporan
                                            Commit Terkait)</h4>

                                        <template x-if="detailTaskData.reports && detailTaskData.reports.length > 0">
                                            <div class="space-y-4">
                                                {{-- Lakukan looping untuk setiap laporan/commit yang terhubung --}}
                                                <template x-for="report in detailTaskData.reports"
                                                    :key="report.id">
                                                    <div class="border border-slate-200 rounded-lg">
                                                        {{-- Header Laporan --}}
                                                        <div class="bg-slate-50 p-3 border-b border-slate-200">
                                                            <p class="font-semibold text-slate-700"
                                                                x-text="report.title">
                                                            </p>
                                                            <p class="text-xs text-slate-500"
                                                                x-text="`Dibuat pada: ${new Date(report.created_at).toLocaleString('id-ID')}`">
                                                            </p>
                                                        </div>
                                                        {{-- Body Laporan --}}
                                                        <div class="p-3 space-y-3">
                                                            <p class="text-xs text-slate-600 italic"
                                                                x-text="report.description"></p>

                                                            {{-- Tampilkan Snippets jika ada --}}
                                                            <template
                                                                x-if="report.code_snippets && report.code_snippets.length > 0">
                                                                <div class="space-y-2">
                                                                    <p class="text-xs font-semibold text-slate-500">
                                                                        Potongan Kode Penting:</p>
                                                                    <template x-for="snippet in report.code_snippets"
                                                                        :key="snippet.id">
                                                                        <div
                                                                            class="border border-slate-200 rounded-md">
                                                                            <p class="text-xs text-slate-500 bg-slate-50 px-2 py-1 border-b"
                                                                                x-text="snippet.description"></p>
                                                                            <pre class="text-xs bg-slate-900 text-white p-2 overflow-x-auto"><code x-text="snippet.content"></code></pre>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Tampilan jika tidak ada laporan terhubung --}}
                                        <template
                                            x-if="!detailTaskData.reports || detailTaskData.reports.length === 0">
                                            <p class="text-slate-500 text-center py-4">Belum ada commit/laporan
                                                pengerjaan
                                                yang terhubung dengan tugas ini.</p>
                                        </template>
                                    </div>
                                </div>
                                <div class="bg-slate-50 px-6 py-4 flex justify-end sticky bottom-0 z-10">
                                    <button @click="showDetailModal = false" type="button"
                                        class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Tutup</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
