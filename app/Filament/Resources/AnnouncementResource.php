<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('admin_name')->required()->label("Admin Name"),
                Forms\Components\TextInput::make('subject')->required()->label("Subject"),
                RichEditor::make('message')
                ->label('Message')
                ->toolbarButtons([
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'link',
                    'orderedList',
                    'unorderedList',
                    'h2',
                    'h3',
                    'blockquote',
                    'codeBlock',
                ])
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label("ID"),
                Tables\Columns\TextColumn::make('admin_name')->label("Post By"),
                Tables\Columns\TextColumn::make('created_at')->label("Create At"),
                Tables\Columns\TextColumn::make('subject')->label("Subject"),
                Tables\Columns\TextColumn::make('message')->label("Message")
                ->html()
                ->wrap(),
                BooleanColumn::make('is_fixed')->label('Fixed'),
                Tables\Columns\TextColumn::make('updated_at')->label("Fixed At"),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('fixed')
                ->color('success')
                ->label('Fixed')
                ->icon("heroicon-o-check-circle")
                ->action(function (Announcement $record) {
                    $record->update(['is_fixed' => true]); // Cek apakah ini berhasil
                    $record->save(); // Tidak perlu save() jika update() berhasil
                    Notification::make()
                        ->title('Fixed.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (Announcement $record) => !$record->is_fixed)
                ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                Tables\Actions\DeleteAction::make()->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                Tables\Actions\EditAction::make()->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user() && in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
