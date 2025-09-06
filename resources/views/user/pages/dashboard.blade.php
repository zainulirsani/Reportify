<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="space-y-6">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">{{ $greeting }}, {{ Auth::user()->name }}!</h2>
            <p class="text-slate-500 mt-1">Berikut adalah ringkasan aktivitas pekerjaanmu hari ini.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 flex items-center space-x-4">
                <div class="bg-indigo-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Commit Hari Ini</p>
                    <p class="text-3xl font-bold text-slate-800">{{ $commitsToday }}</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-slate-200 flex items-center space-x-4">
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.87a3 3 0 01-.879-2.122v-1.007M5.25 6.002a4.5 4.5 0 019 0v6a4.5 4.5 0 01-9 0v-6z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Proyek</p>
                    <p class="text-3xl font-bold text-slate-800">{{ count($systems) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200 rounded-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800">Aktivitas Commit 5 Hari Terakhir</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="commitChart"></canvas>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800">Riwayat Commit Terbaru</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">
                                        Proyek</th>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">
                                        Pesan Commit</th>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500 uppercase tracking-wider">
                                        Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                               @forelse ($recentReports as $report)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-800">
                                            {{ $report->repository }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                            {{ Str::limit($report->message, 50) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $report->time }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-10 text-slate-500">Tidak ada commit
                                            terbaru untuk ditampilkan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white border border-slate-200 rounded-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800">Sistem / Proyek Saya</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach ($systems as $system)
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <p class="font-semibold text-slate-800">{{ $system->name }}</p>
                                <p class="text-sm text-slate-500 truncate">{{ $system->repository_url }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            // Menjalankan script setelah seluruh konten halaman (DOM) selesai dimuat
            document.addEventListener('DOMContentLoaded', function() {

                // Mengambil elemen <canvas> dari HTML
                const ctx = document.getElementById('commitChart').getContext('2d');

                // Mengambil data yang sudah diolah oleh PHP dan mengubahnya menjadi format JSON yang aman
                const chartLabels = @json($chartLabels);
                const chartDatasets = @json($chartDatasets);

                // Pengecekan untuk memastikan data ada sebelum mencoba membuat chart
                if (chartLabels && chartDatasets) {
                    new Chart(ctx, {
                        type: 'line', // Tipe chart adalah line chart
                        data: {
                            labels: chartLabels,
                            datasets: chartDatasets
                        },
                        options: {
                            responsive: true, // Membuat chart responsif terhadap ukuran container
                            plugins: {
                                legend: {
                                    position: 'top', // Posisi legenda (nama sistem) di atas chart
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah Commit Harian per Proyek' // Judul chart
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true, // Sumbu Y dimulai dari angka 0
                                    ticks: {
                                        // Hanya tampilkan angka bulat di sumbu Y (misal: 1, 2, 3 bukan 1.5)
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
