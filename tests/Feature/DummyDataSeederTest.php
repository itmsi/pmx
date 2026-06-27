<?php

use App\Models\ExternalAccess;
use App\Models\GitHistory;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Database\Seeders\DummyDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates dummy data for the main application tables', function () {
    $this->seed(DummyDataSeeder::class);

    expect(User::count())->toBeGreaterThan(0)
        ->and(Project::count())->toBeGreaterThan(0)
        ->and(Ticket::count())->toBeGreaterThan(0)
        ->and(TicketComment::count())->toBeGreaterThan(0)
        ->and(Notification::count())->toBeGreaterThan(0)
        ->and(GitHistory::count())->toBeGreaterThan(0)
        ->and(ExternalAccess::count())->toBeGreaterThan(0);
});
