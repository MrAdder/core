<?php

namespace App\Filament\Training\Resources\SessionBookingSlotResource\Pages;

use App\Filament\Admin\Helpers\Pages\BaseListRecordsPage;
use App\Filament\Training\Resources\SessionBookingSlotResource;
use Filament\Actions;

class ListSessionBookingSlots extends BaseListRecordsPage
{
    protected static string $resource = SessionBookingSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
