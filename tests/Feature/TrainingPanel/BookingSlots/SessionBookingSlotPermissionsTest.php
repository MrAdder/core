<?php

namespace Tests\Feature\TrainingPanel\BookingSlots;

use App\Filament\Training\Resources\SessionBookingSlotResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\TrainingPanel\BaseTrainingPanelTestCase;

class SessionBookingSlotPermissionsTest extends BaseTrainingPanelTestCase
{
    use DatabaseTransactions;

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs($this->panelUser)
            ->get(SessionBookingSlotResource::getUrl('index', panel: 'training'))
            ->assertForbidden();

        $this->panelUser->givePermissionTo('training.booking-slots.view');

        $this->actingAs($this->panelUser)
            ->get(SessionBookingSlotResource::getUrl('index', panel: 'training'))
            ->assertSuccessful();
    }

    public function test_create_requires_manage_permission(): void
    {
        $this->panelUser->givePermissionTo('training.booking-slots.view');

        $this->actingAs($this->panelUser)
            ->get(SessionBookingSlotResource::getUrl('create', panel: 'training'))
            ->assertForbidden();

        $this->panelUser->givePermissionTo('training.booking-slots.manage');

        $this->actingAs($this->panelUser)
            ->get(SessionBookingSlotResource::getUrl('create', panel: 'training'))
            ->assertSuccessful();
    }
}
