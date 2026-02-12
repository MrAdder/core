<?php

namespace App\Policies\Training;

use App\Models\Mship\Account;
use App\Models\Training\SessionBookingSlot;

class SessionBookingSlotPolicy
{
    public function viewAny(Account $account): bool
    {
        return $account->can('training.booking-slots.view');
    }

    public function view(Account $account, SessionBookingSlot $sessionBookingSlot): bool
    {
        return $account->can('training.booking-slots.view');
    }

    public function create(Account $account): bool
    {
        return $account->can('training.booking-slots.manage');
    }

    public function update(Account $account, SessionBookingSlot $sessionBookingSlot): bool
    {
        return $account->can('training.booking-slots.manage');
    }

    public function delete(Account $account, SessionBookingSlot $sessionBookingSlot): bool
    {
        return $account->can('training.booking-slots.manage');
    }
}
