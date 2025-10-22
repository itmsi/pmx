<?php

namespace App\Policies;

use App\Models\GitHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GitHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User dapat melihat git history jika mereka memiliki akses ke ticket
        return $user->can('viewAny', \App\Models\Ticket::class);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GitHistory $gitHistory): bool
    {
        // User dapat melihat git history jika mereka memiliki akses ke ticket terkait
        return $user->can('view', $gitHistory->ticket);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Git history dibuat melalui webhook, bukan oleh user langsung
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GitHistory $gitHistory): bool
    {
        // Git history tidak boleh diupdate setelah dibuat
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GitHistory $gitHistory): bool
    {
        // Hanya admin yang dapat menghapus git history
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GitHistory $gitHistory): bool
    {
        // Hanya admin yang dapat restore git history
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GitHistory $gitHistory): bool
    {
        // Hanya super admin yang dapat force delete git history
        return $user->hasRole('super_admin');
    }
}
