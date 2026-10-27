<?php

namespace App\Http\Traits;

use App\Models\Ticket;
use App\Models\User;

trait FilterAdminsByHospital
{
    /**
     * Get department admins filtered by hospital.
     * Only returns admins who share at least one hospital with the ticket creator.
     * If the ticket creator has no hospitals assigned, any admin of that department
     * will be included.
     *
     * This helper remains for backward compatibility but the preferred method is
     * `getUsersAllowedToView()` which mirrors the full TicketPolicy logic.
     */
    private function getDepartmentAdmins(Ticket $ticket): \Illuminate\Database\Eloquent\Collection
    {
        $creatorHospitalIds = $ticket->creator->hospitals()->pluck('hospitals.id')->toArray();
        
        $query = User::whereHas('departments', function ($q) use ($ticket) {
            $q->where('departments.id', $ticket->category->department_id);
        })->whereHas('role', function ($q) {
            $q->where('name', 'Admin');
        });

        // If creator has no hospitals assigned (global user), return all dept admins
        if (empty($creatorHospitalIds)) {
            return $query->get();
        }

        // Filter admins: only those who share a hospital with creator.
        // Admins with no hospital assignment are treated as "not eligible" when
        // the creator has one or more hospitals (they are not global viewers).
        return $query->get()->filter(function ($admin) use ($creatorHospitalIds) {
            $adminHospitalIds = $admin->hospitals()->pluck('hospitals.id')->toArray();

            // exclude admins without any hospital when creator is scoped
            if (empty($adminHospitalIds)) {
                return false;
            }

            return !empty(array_intersect($creatorHospitalIds, $adminHospitalIds));
        });
    }

    /**
     * Return all users who would be allowed to *view* the given ticket according to
     * the TicketPolicy (excluding the ticket creator).
     * This naturally applies department/assignment and hospital rules exactly like
     * what the UI uses when showing tickets.
     */
    private function getUsersAllowedToView(Ticket $ticket)
    {
        $policy = new \App\Policies\TicketPolicy();
        // fetch all admins and viewers since they are the only ones that ever get
        // notifications. Filtering later will enforce the policy fully.
        $candidates = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['Admin', 'Viewer']);
        })->get();

        return $candidates->filter(function ($user) use ($policy, $ticket) {
            if ($user->id === $ticket->created_by) {
                return false;
            }
            return $policy->view($user, $ticket);
        });
    }
}
