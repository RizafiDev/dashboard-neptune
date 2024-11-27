<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelWhitelistResource\Pages;
use App\Filament\Resources\ChannelWhitelistResource\RelationManagers;
use App\Models\ChannelWhitelist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Artist;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;

class ChannelWhitelistResource extends Resource
{
    protected static ?string $model = ChannelWhitelist::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label("Username")->default(auth()->user()->name)
                ->readonly(fn () => auth()->user()->role === User::ROLE_ARTIST),
                Forms\Components\Select::make('artist_name')
                ->label('Artist Name')
                ->options(Artist::pluck('artist_name', 'artist_name')->toArray())
                ->searchable()
                ->required(),
                Forms\Components\TextInput::make('channel')->required()->label("Youtube Channel Link")
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('username')->label('Username'),
                Tables\Columns\TextColumn::make('artist_name')->label('Artist Name'),
                Tables\Columns\TextColumn::make('channel')->label('Youtube Channel Link'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                BadgeColumn::make('status')
                ->getStateUsing(function ($record) {
                    // Menyesuaikan status berdasarkan nilai di database
                    return match ($record->status) {
                        'review' => 'Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => 'Review',
                    };
                })
                ->colors([
                    'warning' => 'Review',
                    'success' => 'Approved',
                    'danger' => 'Rejected',
                ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                ->label('Approve')
                ->action(fn ($record) => $record->update(['status' => 'approved']))
                ->requiresConfirmation()
                ->color('success')
                ->icon("heroicon-o-check-circle")
                ->visible(fn ($record) => $record->status !== 'approved')
                ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon("heroicon-o-x-circle")
                ->action(fn ($record) => $record->update(['status' => 'rejected']))
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'rejected')
                ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChannelWhitelists::route('/'),
            'create' => Pages\CreateChannelWhitelist::route('/create'),
            'edit' => Pages\EditChannelWhitelist::route('/{record}/edit'),
        ];
    }
}
