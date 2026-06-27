<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\ExternalAccess;
use App\Models\GitHistory;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $this->command->info('Creating dummy users...');

        $users = User::factory(6)->create([
            'password' => Hash::make('password'),
        ]);

        $users->each(function (User $user) {
            $user->assignRole('member');
        });

        $admin = User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin Demo',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super_admin');

        $this->command->info('Creating dummy projects...');

        $projects = collect([
            ['name' => 'Website Redesign', 'description' => 'Redesign marketing site', 'ticket_prefix' => 'WEB'],
            ['name' => 'Mobile App', 'description' => 'Launch MVP mobile app', 'ticket_prefix' => 'APP'],
            ['name' => 'Internal Ops', 'description' => 'Operational workflow improvements', 'ticket_prefix' => 'OPS'],
        ])->map(fn ($data) => Project::create($data));

        $projects->each(function (Project $project) use ($users) {
            $project->members()->sync($users->pluck('id')->take(4)->all());

            $statuses = [
                ['name' => 'Backlog', 'sort_order' => 1, 'color' => '#6B7280', 'is_completed' => false],
                ['name' => 'In Progress', 'sort_order' => 2, 'color' => '#3B82F6', 'is_completed' => false],
                ['name' => 'Review', 'sort_order' => 3, 'color' => '#F59E0B', 'is_completed' => false],
                ['name' => 'Done', 'sort_order' => 4, 'color' => '#10B981', 'is_completed' => true],
            ];

            foreach ($statuses as $statusData) {
                TicketStatus::create(array_merge($statusData, ['project_id' => $project->id]));
            }

            $epics = [
                ['name' => 'Discovery', 'description' => 'Initial research and planning', 'start_date' => now()->subDays(10)->toDateString(), 'end_date' => now()->subDays(2)->toDateString()],
                ['name' => 'Implementation', 'description' => 'Build and integrate features', 'start_date' => now()->subDays(5)->toDateString(), 'end_date' => now()->addDays(10)->toDateString()],
            ];

            foreach ($epics as $epicData) {
                Epic::create(array_merge($epicData, ['project_id' => $project->id]));
            }

            ProjectNote::create([
                'project_id' => $project->id,
                'created_by' => $users->first()->id,
                'title' => 'Kickoff Notes',
                'content' => 'This is a dummy project note for demo purposes.',
                'note_date' => now()->toDateString(),
            ]);

            ExternalAccess::create([
                'project_id' => $project->id,
                'access_token' => Str::random(32),
                'password' => 'demo-pass',
                'is_active' => true,
                'last_accessed_at' => now(),
            ]);
        });

        $this->command->info('Creating dummy ticket priorities...');

        $priorities = [
            ['name' => 'Low', 'color' => '#10B981'],
            ['name' => 'Medium', 'color' => '#F59E0B'],
            ['name' => 'High', 'color' => '#EF4444'],
            ['name' => 'Urgent', 'color' => '#DC2626'],
        ];

        foreach ($priorities as $priorityData) {
            TicketPriority::firstOrCreate(['name' => $priorityData['name']], $priorityData);
        }

        $this->command->info('Creating dummy tickets and related records...');

        $projects->each(function (Project $project) use ($users) {
            $statuses = $project->ticketStatuses()->get();
            $priorities = TicketPriority::all();
            $epics = $project->epics()->get();

            foreach (range(1, 6) as $index) {
                $status = $statuses->random();
                $priority = $priorities->random();
                $epic = $epics->random();
                $creator = $users->random();
                $assignee = $users->random();

                $ticket = Ticket::create([
                    'project_id' => $project->id,
                    'ticket_status_id' => $status->id,
                    'priority_id' => $priority->id,
                    'name' => "{$project->name} Ticket {$index}",
                    'description' => 'Dummy ticket created for local development and demos.',
                    'start_date' => now()->subDays(rand(1, 10))->toDateString(),
                    'due_date' => now()->addDays(rand(2, 14))->toDateString(),
                    'created_by' => $creator->id,
                    'epic_id' => $epic->id,
                ]);

                $ticket->assignees()->syncWithoutDetaching([$assignee->id]);

                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $creator->id,
                    'comment' => 'This is a sample comment for the dummy ticket.',
                ]);

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $creator->id,
                    'ticket_status_id' => $status->id,
                ]);

                GitHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $creator->id,
                    'author_name' => $creator->name,
                    'author_email' => $creator->email,
                    'branch' => 'feature/demo-' . $index,
                    'commit_message' => 'Add dummy changes for ticket ' . $ticket->id,
                    'commit_hash' => Str::random(12),
                    'pushed_at' => now()->subHours(rand(1, 24)),
                    'repository_name' => 'demo-repo',
                    'repository_url' => 'https://example.com/demo-repo',
                ]);

                Notification::create([
                    'user_id' => $creator->id,
                    'type' => 'comment_added',
                    'title' => 'New update',
                    'message' => 'A new dummy notification was created.',
                    'data' => ['ticket_id' => $ticket->id],
                    'read_at' => rand(0, 1) ? now() : null,
                ]);
            }
        });
    }
}
