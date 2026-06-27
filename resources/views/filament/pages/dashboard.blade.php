<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-500">Projects</h2>
                        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalProjects }}</p>
                    </div>
                    <div class="rounded-full bg-blue-50 text-blue-600 p-3">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                    </div>
                </div>
                <p class="text-sm text-gray-500">Total projects currently available in the system.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-500">Tickets</h2>
                        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalTickets }}</p>
                    </div>
                    <div class="rounded-full bg-green-50 text-green-600 p-3">
                        <x-heroicon-o-clipboard-document class="w-5 h-5" />
                    </div>
                </div>
                <p class="text-sm text-gray-500">All tickets created across the selected projects.</p>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-500">My Assigned Tickets</h2>
                            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $myAssignedTickets }}</p>
                        </div>
                        <div class="rounded-full bg-yellow-50 text-yellow-600 p-3">
                            <x-heroicon-o-user class="w-5 h-5" />
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Tickets currently assigned to your account.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-500">Team Members</h2>
                            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $teamMembers }}</p>
                        </div>
                        <div class="rounded-full bg-purple-50 text-purple-600 p-3">
                            <x-heroicon-o-user-group class="w-5 h-5" />
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Active users with access to the admin panel.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Tickets by Project</h3>
                        <p class="text-sm text-gray-500">Count of tickets grouped by project.</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="projectTicketsChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Monthly Ticket Trend</h3>
                        <p class="text-sm text-gray-500">Tickets created per month over time.</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="monthlyTrendChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Users and Workload</h3>
                    <p class="text-sm text-gray-500">Projects and assigned tickets by user.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="h-80">
                    <canvas id="userProjectsChart" class="w-full h-full"></canvas>
                </div>
                <div class="h-80">
                    <canvas id="userAssignedChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            function createChart(context, type, data, options = {}) {
                if (!context) {
                    return;
                }

                return new Chart(context, {
                    type: type,
                    data: data,
                    options: Object.assign({
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 12,
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#6b7280',
                                },
                                grid: {
                                    display: false,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#6b7280',
                                },
                                grid: {
                                    color: 'rgba(229, 231, 235, 0.8)',
                                },
                            },
                        },
                    }, options),
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const projectTicketsCtx = document.getElementById('projectTicketsChart');
                const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
                const userProjectsCtx = document.getElementById('userProjectsChart');
                const userAssignedCtx = document.getElementById('userAssignedChart');

                const sharedLabels = @json($ticketProjectLabels);
                const sharedCounts = @json($ticketProjectCounts);
                const monthlyLabels = @json($monthlyTrendLabels);
                const monthlyCounts = @json($monthlyTrendCounts);
                const userLabels = @json($userStatsLabels);
                const userProjects = @json($userProjects);
                const userAssigned = @json($userAssignedCounts);

                createChart(projectTicketsCtx, 'bar', {
                    labels: sharedLabels,
                    datasets: [{
                        label: 'Tickets',
                        data: sharedCounts,
                        backgroundColor: sharedCounts.map(() => 'rgba(59, 130, 246, 0.65)'),
                        borderColor: sharedCounts.map(() => 'rgba(37, 99, 235, 0.85)'),
                        borderWidth: 1,
                    }],
                }, {
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },
                });

                createChart(monthlyTrendCtx, 'line', {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Tickets Created',
                        data: monthlyCounts,
                        fill: true,
                        backgroundColor: 'rgba(16, 185, 129, 0.16)',
                        borderColor: 'rgba(16, 185, 129, 0.9)',
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                    }],
                });

                createChart(userProjectsCtx, 'bar', {
                    labels: userLabels,
                    datasets: [{
                        label: 'Projects',
                        data: userProjects,
                        backgroundColor: 'rgba(139, 92, 246, 0.65)',
                        borderColor: 'rgba(124, 58, 237, 0.85)',
                        borderWidth: 1,
                    }],
                });

                createChart(userAssignedCtx, 'bar', {
                    labels: userLabels,
                    datasets: [{
                        label: 'Assigned Tickets',
                        data: userAssigned,
                        backgroundColor: 'rgba(249, 115, 22, 0.65)',
                        borderColor: 'rgba(249, 115, 22, 0.9)',
                        borderWidth: 1,
                    }],
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
