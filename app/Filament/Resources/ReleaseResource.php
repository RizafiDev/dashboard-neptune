<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages;
use App\Models\Release;
use Filament\Forms;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Artist;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use App\Services\SpotifyService;
use App\Services\YouTubeService;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

    protected static ?int $sort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->label('Release Tittle')->required()->maxLength(255),
                Forms\Components\TextInput::make('upc')->label('UPC')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('isrc')->label('ISRC')->maxLength(255)->nullable(),
                 Forms\Components\Select::make('artist_name')
            ->label('Artist Name')
            ->options(Artist::pluck('artist_name', 'artist_name')->toArray())
            ->searchable()
            ->required(),
                Forms\Components\TextInput::make('featuring')->label('Artist Featuring') ->maxLength(255),
                Forms\Components\Select::make('type')->label('Type')->options(Release::TYPE)->required(),
                Forms\Components\Select::make('explicit')->label('Explicit')->options(Release::EXPLICIT)->required(),
                Forms\Components\TextInput::make('email')->required()->maxLength(255)->email()->default(auth()->user()->email) // Set default email dari sesi pengguna
                ->readonly(fn () => auth()->user()->role === User::ROLE_ARTIST),
                Forms\Components\FileUpload::make('image_file_path')->label('Artwork (3000 X 3000)')->required()->preserveFilenames(),
                Forms\Components\FileUpload::make('file_path')->label('Music (.WAV)')->required()->preserveFilenames(),
                
            ]);
    }   

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
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
                Tables\Columns\TextColumn::make('upc')->label('UPC'),
                Tables\Columns\TextColumn::make('isrc')->label('ISRC'),
                Tables\Columns\TextColumn::make('artist_name')
                ->label('Artist Name'),
                Tables\Columns\TextColumn::make('title')->label('Release Tittle'),
                Tables\Columns\TextColumn::make('featuring')->label('Artist Featuring'),
                Tables\Columns\TextColumn::make('image_file_path')
    ->formatStateUsing(fn ($state) => $state 
        ? '<a href="' . url('storage/' . $state) . '" download class="flex items-center justify-center gap-3 text-sm font-medium  font-inter">
              <img src="' . url('storage/' . $state) . '" alt="Artwork" style="max-width: 50px; max-height: 50px; object-fit: cover;">
              Download Artwork
           </a>'
        : 'No image')
    ->html()
    ->label('Artwork'),


                Tables\Columns\TextColumn::make('type')->label('Type'),
                Tables\Columns\TextColumn::make('explicit')->label('Explicit'),
                Tables\Columns\TextColumn::make('file_path')
    ->formatStateUsing(fn ($state) => $state
        ? '<a href="' . url('storage/' . $state) . '" download class="flex items-center justify-center gap-3 text-sm font-medium  font-inter">
              <audio controls>
                  <source src="' . url('storage/' . $state) . '" type="audio/mpeg">
                  Your browser does not support the audio element.
              </audio>
              Download
           </a>'
        : 'No file')
    ->html()
    ->label('Music'),

                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')
                ->label('Create At'),

                Tables\Columns\TextColumn::make('streams')
                ->label('Streams')
                ->getStateUsing(function ($record) {
                  $youtubeService = new YouTubeService();
                 return $youtubeService->getTrackViews($record->upc, $record->artist_name, $record->title);
                }),

            
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Jika user adalah admin atau editor, tampilkan semua data
                if (in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])) {
                    return $query;
                }
                
                // Jika user adalah artist, hanya tampilkan data miliknya
                return $query->where('email', auth()->user()->email);
            })
            ->filters([
                SelectFilter::make('status')
    ->options([
        'review' => 'Reviewing',
        'approved' => 'Approved',
    ])
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
                Action::make('export')
                ->label('Export Metadata')
                ->icon('heroicon-m-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // All data to export
                    $releases = Release::all();
                    $fileName = 'releases_metadata.csv';

                    $headers = [
                        'Content-type'        => 'text/csv',
                        'Content-Disposition' => "attachment; filename=$fileName",
                        'Pragma'              => 'no-cache',
                        'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                        'Expires'             => '0',
                    ];

                    $columns = $releases->first() ? array_keys($releases->first()->toArray()) : [];

                    $callback = function() use ($releases, $columns) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);

                        foreach ($releases as $release) {
                            fputcsv($file, $release->toArray());
                        }
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                })
                ->requiresConfirmation(),
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                    BulkAction::make('export')
                ->label('Export Metadata')
                ->icon('heroicon-m-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // All data to export
                    $releases = Release::all();
                    $fileName = 'releases_metadata.csv';

                    $headers = [
                        'Content-type'        => 'text/csv',
                        'Content-Disposition' => "attachment; filename=$fileName",
                        'Pragma'              => 'no-cache',
                        'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                        'Expires'             => '0',
                    ];

                    $columns = $releases->first() ? array_keys($releases->first()->toArray()) : [];

                    $callback = function() use ($releases, $columns) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);

                        foreach ($releases as $release) {
                            fputcsv($file, $release->toArray());
                        }
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                })
                ->requiresConfirmation(),
                ]
                
                )->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Jika user adalah admin atau editor, tampilkan semua data
        if (in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])) {
            return $query;
        }
        
        // Jika user adalah artist, hanya tampilkan data miliknya
        return $query->where('email', auth()->user()->email);
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
            'index' => Pages\ListReleases::route('/'),
            'create' => Pages\CreateRelease::route('/create'),
            'edit' => Pages\EditRelease::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, [User::ROLE_ARTIST, User::ROLE_ADMIN, User::ROLE_EDITOR]);

    }

    public static function canView($record): bool
    {
        return in_array(auth()->user()->role, [User::ROLE_ARTIST, User::ROLE_ADMIN, User::ROLE_EDITOR]);

    }

}
