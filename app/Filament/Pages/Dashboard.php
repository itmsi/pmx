<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = -2;
    protected static string $routePath = '/';

    public int $totalProjects = 0;
    public int $totalTickets = 0;
    public int $myAssignedTickets = 0;
    public int $teamMembers = 0;
    public array $ticketProjectLabels = [];
    public array $ticketProjectCounts = [];
    public array $monthlyTrendLabels = [];
    public array $monthlyTrendCounts = [];
    public array $userStatsLabels = [];
    public array $userProjects = [];
    public array $userAssignedCounts = [];

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    protected function loadDashboardData(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');

        $projectsQuery = Project::query();
        $ticketsQuery = Ticket::query();
        $usersQuery = User::query();

        if (!$isSuperAdmin) {
            $projectsQuery->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

            $ticketsQuery->whereHas('project.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

            $usersQuery->where('id', $user->id);
        }

        $this->totalProjects = $projectsQuery->count();
        $this->totalTickets = $ticketsQuery->count();
        $this->teamMembers = $isSuperAdmin ? User::count() : $usersQuery->count();

        $this->myAssignedTickets = $user
            ? $user->assignedTickets()->count()
            : 0;

        $ticketProjects = Project::query()
            ->withCount('tickets')
            ->when(!$isSuperAdmin, function ($query) use ($user) {
                $query->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->orderBy('name')
            ->get();

        $this->ticketProjectLabels = $ticketProjects->pluck('name')->toArray();
        $this->ticketProjectCounts = $ticketProjects->pluck('tickets_count')->toArray();

        $this->loadMonthlyTrendData($isSuperAdmin, $user);
        $this->loadUserStatsData($isSuperAdmin, $user);
    }

    protected function loadMonthlyTrendData(bool $isSuperAdmin, ?User $user): void
    {
        $earliestTicketQuery = Ticket::query();

        if (!$isSuperAdmin && $user) {
            $earliestTicketQuery->whereHas('project.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $earliestTicket = $earliestTicketQuery->orderBy('created_at')->first();

        if (!$earliestTicket) {
            $this->monthlyTrendLabels = [];
            $this->monthlyTrendCounts = [];
            return;
        }

        $startDate = Carbon::parse($earliestTicket->created_at)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $months = [];

        while ($startDate->lte($endDate)) {
            $months[] = $startDate->copy();
            $startDate->addMonth();
        }

        $this->monthlyTrendLabels = array_map(fn($month) => $month->format('M Y'), $months);

        $driver = DB::getDriverName();
        $getYear = $driver === 'pgsql' ? 'EXTRACT(YEAR FROM created_at)' : 'YEAR(created_at)';
        $getMonth = $driver === 'pgsql' ? 'EXTRACT(MONTH FROM created_at)' : 'MONTH(created_at)';
        $yearKey = $driver === 'pgsql' ? 'year' : 'year';
        $monthKey = $driver === 'pgsql' ? 'month' : 'month';

        $ticketData = Ticket::query()
            ->select(
                DB::raw("$getYear AS year"),
                DB::raw("$getMonth AS month"),
                DB::raw('COUNT(*) AS total')
            )
            ->groupByRaw("$yearKey, $monthKey")
            ->orderByRaw("$yearKey, $monthKey")
            ->when(!$isSuperAdmin && $user, function ($query) use ($user) {
                $query->whereHas('project.members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->get()
            ->keyBy(fn ($item) => sprintf('%04d-%02d', $item->year, $item->month));

        $this->monthlyTrendCounts = array_map(function ($month) use ($ticketData) {
            $key = $month->format('Y-m');
            return $ticketData->get($key)->total ?? 0;
        }, $months);
    }

    protected function loadUserStatsData(bool $isSuperAdmin, ?User $user): void
    {
        $usersQuery = User::query()
            ->withCount([
                'projects as total_projects',
                'assignedTickets as total_assigned_tickets',
            ])
            ->orderBy('name');

        if (!$isSuperAdmin && $user) {
            $usersQuery->where('id', $user->id);
        }

        $users = $usersQuery->get();

        $this->userStatsLabels = $users->pluck('name')->toArray();
        $this->userProjects = $users->pluck('total_projects')->toArray();
        $this->userAssignedCounts = $users->pluck('total_assigned_tickets')->toArray();
    }
}
