<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isRoot()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // The ticket's creator can always view their own ticket.
        if ($user->id === $ticket->created_by) {
            return true;
        }

        // For other users (Admins/Viewers), they must be authorized by department/assignment...
        $isAuthorizedByRole = ($ticket->category?->mainDepartment?->users()->where('users.id', $user->id)->exists())
            || $ticket->assignedUsers->contains($user->id);

        // ...and they must also pass the hospital check.
        return $isAuthorizedByRole && $this->shareHospital($user, $ticket);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Admins/Viewers must be authorized by department/assignment...
        $isAuthorizedByRole = ($ticket->category?->department?->users()->where('users.id', $user->id)->exists())
            || $ticket->assignedUsers->contains($user->id);

        // ...and they must also pass the hospital check.
        return $isAuthorizedByRole && $this->shareHospital($user, $ticket);
    }

    /**
     * Check if the user shares at least one hospital with the ticket creator.
     * - If the user (admin/viewer) has no hospitals assigned, they are considered a "global" user
     *   and can see tickets from all hospitals. This is a common case for IT staff.
     * - If the user has hospitals, the ticket creator must be in one of those hospitals.
     */
    private function shareHospital(User $user, Ticket $ticket): bool
    {
        $userHospitalIds = $user->hospitals()->pluck('hospitals.id');

        // If the viewing user (e.g., an admin) has no hospitals assigned, they are not restricted.
        // This allows IT or global admins (whose OUs might not map to a hospital) to see all tickets.
        if ($userHospitalIds->isEmpty()) {
            return true;
        }

        // If the admin is assigned to specific hospitals, the ticket creator must belong to one of them.
        return $ticket->creator && $ticket->creator->hospitals()->whereIn('hospitals.id', $userHospitalIds)->exists();
    }


}
