<?php

namespace App\Filament\Training\Resources;

use App\Filament\Training\Resources\SessionBookingSlotResource\Pages;
use App\Models\Training\SessionBookingSlot;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SessionBookingSlotResource extends Resource
{
    protected static ?string $model = SessionBookingSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Training';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Booking Slot';

    protected static ?string $pluralModelLabel = 'Booking Slots';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('session_type')
                ->label('Slot Type')
                ->options(SessionBookingSlot::typeOptions())
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            DateTimePicker::make('scheduled_for')->seconds(false)->required(),
            TextInput::make('duration_minutes')->numeric()->required()->minValue(15)->default(90),
            Textarea::make('notes')->rows(3)->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_for')
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_for')->label('Scheduled For')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('session_type')->label('Type')->formatStateUsing(fn (string $state): string => SessionBookingSlot::typeOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration (min)'),
                Tables\Columns\TextColumn::make('picked_up_by_name')->label('Picked Up By')->default('Unassigned'),
                Tables\Columns\IconColumn::make('picked_up_at')->label('Assigned')->timestampBoolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_type')->label('Type')->options(SessionBookingSlot::typeOptions()),
                Tables\Filters\TernaryFilter::make('picked_up_at')->label('Assigned')->placeholder('Any')->trueLabel('Assigned')->falseLabel('Open'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessionBookingSlots::route('/'),
            'create' => Pages\CreateSessionBookingSlot::route('/create'),
            'edit' => Pages\EditSessionBookingSlot::route('/{record}/edit'),
        ];
    }
}
