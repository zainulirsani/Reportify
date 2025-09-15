<x-app-layout>
    <x-slot name="header">
        {{ __('Laporan Mingguan') }}
    </x-slot>

    {{-- "Otak" Alpine.js untuk loading state dan copy to clipboard --}}
    <div x-data="{
        isLoading: false,
        copySuccess: false,
        copyToClipboard() {
            const reportOutput = document.getElementById('report-output-content');
            if (!reportOutput) {
                alert('Tidak ada laporan untuk disalin.');
                return;
            }
            // Membuat teks dengan format yang rapi untuk di-copy
            let fullReportText = 'Laporan Mingguan\n================================\n\n';
            const summaries = reportOutput.querySelectorAll('.system-summary');
            summaries.forEach((summary, index) => {
                const title = summary.querySelector('h4').innerText;
                const tasks = Array.from(summary.querySelectorAll('li')).map(li => `- ${li.innerText}`).join('\n');
                const paragraph = summary.querySelector('p').innerText;
                fullReportText += `${title}\n- Ringkasan Pekerjaan:\n${tasks}\n\n- Detail:\n${paragraph}\n\n`;
            });

            navigator.clipboard.writeText(fullReportText.trim());
            // ... (logika copySuccess) ...
        }
    }" class="max-w-4xl mx-auto space-y-6">

        {{-- Menampilkan notifikasi dari controller --}}
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>{{ session('error') }}</p></div>
        @endif
        @if (session('info'))
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert"><p>{{ session('info') }}</p></div>
        @endif

        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200">
            <h2 class="text-2xl font-bold text-slate-800">Generate Laporan Mingguan</h2>
            <p class="text-slate-500 mt-2">Pilih kategori proyek, sistem akan mengambil laporan harian yang "Completed" dari 7 hari terakhir dan meminta AI untuk membuat ringkasannya.</p>
            
            <form action="{{ route('reports.weekly.generate') }}" method="POST" @submit="isLoading = true">
                @csrf
                
                {{-- TAMBAHKAN BLOK PILIHAN KATEGORI INI --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-700">Pilih Kategori Laporan</label>
                    <fieldset class="mt-2">
                        <legend class="sr-only">Tipe Notifikasi</legend>
                        <div class="space-y-2 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
                            <div class="flex items-center">
                                <input id="cat_all" name="category" type="radio" value="all" checked class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="cat_all" class="ml-3 block text-sm font-medium text-gray-700">Semua Proyek</label>
                            </div>
                            <div class="flex items-center">
                                <input id="cat_internal" name="category" type="radio" value="internal" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="cat_internal" class="ml-3 block text-sm font-medium text-gray-700">Proyek Internal</label>
                            </div>
                            <div class="flex items-center">
                                <input id="cat_eksternal" name="category" type="radio" value="eksternal" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="cat_eksternal" class="ml-3 block text-sm font-medium text-gray-700">Proyek Eksternal</label>
                            </div>
                        </div>
                         @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </fieldset>
                </div>
                
                <div class="mt-6 pt-4 border-t border-slate-200">
                   <button type="submit" 
                            :disabled="isLoading"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold text-base hover:bg-indigo-700 transition-colors duration-200 disabled:bg-indigo-300 disabled:cursor-not-allowed">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Sedang Memproses...' : 'Buat Ringkasan Minggu Ini'"></span>
                    </button>
                </div>
            </form>
        </div>

         @if (isset($systems_summary) && !empty($systems_summary))
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-slate-800">Hasil Laporan Mingguan</h3>
                {{-- Tombol Copy to Clipboard --}}
                <div class="relative">
                    <button @click="copyToClipboard()" class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 text-sm font-semibold flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span>Copy Laporan</span>
                    </button>
                    <span x-show="copySuccess" x-transition class="absolute -top-7 right-0 bg-slate-800 text-white text-xs rounded-md px-2 py-1">Copied!</span>
                </div>
            </div>
            
            <div class="p-6 sm:p-8 space-y-8" id="report-output-content">
                {{-- Lakukan looping untuk setiap sistem --}}
                @foreach($systems_summary as $system)
                    <div class="system-summary">
                        <h4 class="text-xl font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3">{{ $system['name'] }}</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Kolom Kiri: Poin-poin pekerjaan --}}
                            <div class="md:col-span-1">
                                <h5 class="font-semibold text-slate-600 mb-2">Poin Pekerjaan:</h5>
                                <ul class="list-disc list-inside space-y-1 text-slate-500 text-sm">
                                    @forelse($system['tasks'] as $task)
                                        <li>{{ $task }}</li>
                                    @empty
                                        <li>Tidak ada detail pekerjaan.</li>
                                    @endforelse
                                </ul>
                            </div>
                            {{-- Kolom Kanan: Paragraf Penjelasan --}}
                            <div class="md:col-span-2">
                                <h5 class="font-semibold text-slate-600 mb-2">Ringkasan Naratif:</h5>
                                <p class="text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $system['summary_paragraph'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-app-layout>