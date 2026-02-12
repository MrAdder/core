<?php

namespace Tests\Feature\TrainingPanel\BookingSlots;

use App\Filament\Training\Resources\SessionBookingSlotResource;
use App\Policies\Training\SessionBookingSlotPolicy;
use Tests\Feature\TrainingPanel\BaseTrainingPanelResourceTestCase;

class SessionBookingSlotResourceTest extends BaseTrainingPanelResourceTestCase
{
    protected static ?string $resourceClass = SessionBookingSlotResource::class;

    protected ?string $policy = SessionBookingSlotPolicy::class;
}
