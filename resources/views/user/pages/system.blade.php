<x-app-layout>
    <x-slot name="header">
        {{ __('Manajemen Sistem / Proyek') }}
    </x-slot>

    {{-- "Otak" Alpine.js untuk mengelola state panel --}}
    <div x-data="{
        // State untuk panel edit/create sistem
        showPanel: false,
        isEditMode: false,
        formTitle: '',
        formUrl: '',
        systemData: { id: null, name: '', repository_url: '', description: '', category: 'internal' },

        // State BARU untuk modal sync
        showSyncModal: false,
        isLoadingSyncPreview: false,
        isProcessingSync: false,
        syncPreviewData: { new_commits: [], open_tasks: [] },
        commitMappings: [],
        syncingSystem: null,

        openCreatePanel() {
            this.isEditMode = false;
            this.formTitle = 'Tambah Sistem Baru';
            this.formUrl = '{{ route('systems.store') }}';
            this.systemData = { id: null, name: '', repository_url: '', description: '', category: 'internal' };
            this.showPanel = true;
        },
        openEditPanel(system) {
            this.isEditMode = true;
            this.formTitle = 'Edit Sistem';
            this.formUrl = `/systems/${system.id}`;
            this.systemData = {
                id: system.id,
                name: system.name,
                repository_url: system.repository_url,
                description: system.description || '',
                category: system.category || 'internal'
            };
            this.showPanel = true;
        },

        // Fungsi BARU untuk membuka modal sync
        openSyncModal(system) {
            this.syncingSystem = system; // Simpan data sistem yang sedang disinkronkan
            this.isLoadingSyncPreview = true;
            this.showSyncModal = true;
            this.syncPreviewData = { new_commits: [], open_tasks: [] }; // Reset data lama

            fetch(`/systems/${system.id}/sync/preview`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    this.syncPreviewData = data;
                    // Siapkan array untuk menampung pemetaan (commit -> tugas)
                    this.commitMappings = data.new_commits.map(commit => ({
                        commit: commit,
                        task_id: '' // Defaultnya tidak terhubung ke tugas manapun
                    }));
                })
                .catch(error => {
                    alert(error.message);
                    this.showSyncModal = false;
                })
                .finally(() => this.isLoadingSyncPreview = false);
        }
    }" class="space-y-6">

        {{-- KONTEN UTAMA HALAMAN (Tabel, dll) --}}
        <div class="flex justify-between items-center">
            <p class="text-slate-500">Daftar semua proyek yang laporannya ingin Anda lacak.</p>
            {{-- Tombol ini sekarang memanggil fungsi Alpine.js --}}
            <button @click="openCreatePanel()"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold">
                + Tambah Sistem
            </button>
        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    {{-- ... Konten thead sama seperti sebelumnya ... --}}
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Nama
                                Sistem</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">URL
                                Repositori</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($systems as $system)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-800">{{ $system->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $system->repository_url }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    {{-- Tombol Edit juga memanggil fungsi Alpine.js --}}
                                    <button @click="openEditPanel({{ json_encode($system) }})"
                                        class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click.prevent="openSyncModal({{ $system }})" class="text-green-600 hover:text-green-900">Sync</button>
                                    <form action="{{ route('systems.destroy', $system) }}" method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus sistem ini?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-10 text-slate-500">Anda belum menambahkan
                                    sistem apapun.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($systems->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $systems->links() }}
                </div>
            @endif
        </div>

        {{-- PANEL GESER (SLIDE-OVER PANEL) --}}
        <div x-show="showPanel" style="display: none;" class="relative z-50" x-cloak>
            {{-- Backdrop --}}
            <div x-show="showPanel" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div x-show="showPanel"
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-md">
                    <form :action="formUrl" method="POST"
                        class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        @csrf
                        {{-- Method spoofing untuk Edit --}}
                        <template x-if="isEditMode">
                            @method('PUT')
                        </template>

                        {{-- Header Panel --}}
                        <div class="bg-indigo-700 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-white" x-text="formTitle"></h2>
                                <button @click="showPanel = false" type="button"
                                    class="rounded-md bg-indigo-700 text-indigo-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Konten Form --}}
                        <div class="relative flex-1 px-4 py-6 sm:px-6 space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700">Nama
                                    Sistem</label>
                                <input type="text" name="name" id="name" x-model="systemData.name" required
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="repository_url" class="block text-sm font-medium text-slate-700">URL
                                    Repositori GitHub</label>
                                <input type="url" name="repository_url" id="repository_url"
                                    x-model="systemData.repository_url" required
                                    placeholder="https://github.com/user/nama-repo"
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi
                                    (Opsional)</label>
                                <textarea name="description" id="description" rows="3" x-model="systemData.description"
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-slate-700">Kategori Sistem</label>
                                <select name="category" id="category" x-model="systemData.category"
                                    class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="internal">Internal</option>
                                    <option value="eksternal">Eksternal</option>
                                </select>
                            </div>
                        </div>

                        {{-- Footer Panel (Tombol Aksi) --}}
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
        <div x-show="showSyncModal" style="display: none;" x-cloak class="relative z-50">
            <div x-show="showSyncModal" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-60"></div>
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <form action="{{ route('systems.sync.process') }}" method="POST" @submit="isProcessingSync = true" @click.away="showSyncModal = false" x-show="showSyncModal" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all">
                        @csrf
                        {{-- Kirim system_id secara tersembunyi --}}
                        <input type="hidden" name="system_id" :value="syncingSystem ? syncingSystem.id : ''">

                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-800">Hubungkan Commit dengan Tugas</h3>
                        </div>
                        
                        <div class="p-6 max-h-[70vh] overflow-y-auto">
                            {{-- Tampilan Loading --}}
                            <div x-show="isLoadingSyncPreview" class="text-center py-8">
                                <p class="text-slate-500">Mencari commit baru di GitHub...</p>
                            </div>

                            {{-- Tampilan Hasil --}}
                            <div x-show="!isLoadingSyncPreview">
                                {{-- Jika tidak ada commit baru --}}
                                <template x-if="commitMappings.length === 0">
                                    <p class="text-center py-8 text-slate-500">Tidak ada commit baru yang ditemukan untuk disinkronkan.</p>
                                </template>

                                {{-- Jika ada commit baru --}}
                                <template x-if="commitMappings.length > 0">
                                    <div class="space-y-4">
                                        <p class="text-sm text-slate-600">Ditemukan <strong x-text="commitMappings.length"></strong> commit baru. Silakan hubungkan setiap commit dengan tugas yang relevan. Commit yang terhubung akan mengubah status tugas menjadi "Done".</p>
                                        
                                        <div class="space-y-3">
                                            <template x-for="(mapping, index) in commitMappings" :key="mapping.commit.sha">
                                                <div class="grid grid-cols-2 gap-4 items-center">
                                                    {{-- Info Commit --}}
                                                    <div class="text-sm bg-slate-50 p-3 rounded-md border">
                                                        <p class="font-semibold text-slate-700 truncate" x-text="mapping.commit.commit.message.split('\n\n')[0]"></p>
                                                        <p class="text-xs text-slate-500" x-text="`oleh ${mapping.commit.commit.author.name}`"></p>
                                                    </div>
                                                    {{-- Dropdown Pilihan Tugas --}}
                                                    <div>
                                                        {{-- Kirim data commit sebagai JSON tersembunyi --}}
                                                        <input type="hidden" :name="`mappings[${index}][commit]`" :value="JSON.stringify(mapping.commit)">
                                                        
                                                        <select :name="`mappings[${index}][task_id]`" x-model="mapping.task_id" class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                                            <option value="">-- Tidak Terkait Tugas --</option>
                                                            <template x-for="task in syncPreviewData.open_tasks" :key="task.id">
                                                                <option :value="task.id" x-text="`${task.task_code}: ${task.title}`"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex justify-end space-x-3">
                            <button @click.prevent="showSyncModal = false" type="button" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Batal</button>
                            <button type="submit" x-show="!isLoadingSyncPreview && commitMappings.length > 0" :disabled="isProcessingSync" class="inline-flex justify-center rounded-md border border-transparent bg-green-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:bg-green-300">
                                <svg x-show="isProcessingSync" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isProcessingSync ? 'Memproses...' : 'Proses Laporan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
