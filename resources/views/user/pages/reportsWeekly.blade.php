<x-app-layout>
    <x-slot name="header">
        {{ __('Laporan Mingguan') }}
    </x-slot>

    {{-- "Otak" Alpine.js untuk loading state dan copy to clipboard --}}
    <div x-data="{
        isLoading: false,
        copySuccess: false,
        copyToClipboard() {
            // Cek apakah elemen hasil laporan ada di halaman
            if (!document.getElementById('summary-paragraph')) {
                alert('Tidak ada laporan untuk disalin.');
                return;
            }
            const summaryTitle = document.getElementById('summary-title').innerText;
            const summaryParagraph = document.getElementById('summary-paragraph').innerText;
            const systemsTitle = document.getElementById('systems-title').innerText;
            const systemsList = Array.from(document.querySelectorAll('#systems-list li')).map(li => `- ${li.innerText}`).join('\n');
            
            const fullReportText = `${summaryTitle}\n\n${summaryParagraph}\n\n${systemsTitle}\n${systemsList}`;

            navigator.clipboard.writeText(fullReportText).then(() => {
                this.copySuccess = true;
                setTimeout(() => {
                    this.copySuccess = false;
                }, 2000);
            });
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
            <p class="text-slate-500 mt-2">Sistem akan mengambil semua laporan harian Anda yang berstatus "Completed" dari 7 hari terakhir, lalu meminta AI untuk membuat ringkasan profesional dalam bentuk paragraf beserta daftar sistem yang dikerjakan.</p>
            <div class="mt-6">
                <form action="{{ route('reports.weekly.generate') }}" method="POST" @submit="isLoading = true">
                    @csrf
                    <button type="submit" 
                            :disabled="isLoading"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold text-base hover:bg-indigo-700 transition-colors duration-200 disabled:bg-indigo-300 disabled:cursor-not-allowed">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Sedang Memproses...' : 'Buat Ringkasan Minggu Ini'"></span>
                    </button>
                </form>
            </div>
        </div>

        @if (isset($summary) && isset($systems))
        <div class="bg-white rounded-xl border border-slate-200" id="report-output">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h3 id="summary-title" class="text-lg font-semibold text-slate-800">Hasil Laporan Mingguan</h3>
                <div class="relative">
                    <button @click="copyToClipboard()" class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 text-sm font-semibold flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span>Copy</span>
                    </button>
                    <span x-show="copySuccess" x-transition class="absolute -top-7 right-0 bg-slate-800 text-white text-xs rounded-md px-2 py-1">Copied!</span>
                </div>
            </div>
            
            <div class="p-6 sm:p-8 space-y-6">
                <div>
                    <p id="summary-paragraph" class="text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $summary }}</p>
                </div>
                <div>
                    <h4 id="systems-title" class="font-semibold text-slate-800 mb-2">Sistem/Proyek yang Dikerjakan:</h4>
                    <ul id="systems-list" class="list-disc list-inside space-y-1 text-slate-600">
                        {{-- INI CARA YANG BENAR UNTUK MENAMPILKAN ARRAY $systems --}}
                        @forelse ($systems as $systemName)
                            <li>{{ $systemName }}</li>
                        @empty
                            <li>Tidak ada sistem spesifik yang tercatat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>