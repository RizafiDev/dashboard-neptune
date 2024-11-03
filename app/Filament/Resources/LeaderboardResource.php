<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaderboardResource\Pages;
use App\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Release;
use App\Models\Revenue;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class LeaderboardResource extends Resource
{
    protected static ?string $model = Artist::class;


    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Leaderboard';

    protected static ?int $sort = 3;

    protected static ?string $modelLabel = 'Artist Leaderboard';


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->getStateUsing(function ($record, $livewire) {
                        $sortField = $livewire->getTableSortColumn() ?? 'total_releases';
                        $query = Artist::query();
                        
                        if ($sortField === 'total_releases') {
                            $query->withCount(['releases as release_count'])
                                ->orderByDesc('release_count');
                        } else {
                            $query->withSum('revenues', 'revenue_amount')
                                ->orderByDesc('revenues_sum_revenue_amount');
                        }
                        
                        $position = $query->get()->search(function($item) use ($record) {
                            return $item->id === $record->id;
                        });
                        
                        return $position !== false ? $position + 1 : 'N/A';
                    }),
                
                Tables\Columns\TextColumn::make('artist_avatar')
                    ->formatStateUsing(fn ($state) => $state 
                        ? '<img src="' . url('storage/' . $state) . '" alt="Avatar" style="max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 50%;">'
                        : '❌')
                    ->html()
                    ->label('Avatar'),
                
                Tables\Columns\TextColumn::make('artist_name')
                    ->label('Artist Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_releases')
                    ->label('Total Releases')
                    ->getStateUsing(fn ($record) => Release::where('artist_name', $record->artist_name)->count())
                    ->sortable(query: function (Builder $query) {
                        return $query->withCount('releases')->orderBy('releases_count');
                    }),
                
                Tables\Columns\TextColumn::make('total_royalties')
                    ->label('Total Royalties')
                    ->getStateUsing(fn ($record) => '$' . number_format(
                        Revenue::where('artist_name', $record->artist_name)->sum('revenue_amount'), 
                        2
                    ))
                    ->sortable(query: function (Builder $query) {
                        return $query->withSum('revenues', 'revenue_amount')
                            ->orderBy('revenues_sum_revenue_amount');
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Join Date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('total_releases', 'desc')
            ->filters([
                SelectFilter::make('ranking_by')
                    ->label('Ranking By')
                    ->options([
                        'releases' => 'Total Releases',
                        'royalties' => 'Total Royalties',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        
                        if ($data['value'] === 'releases') {
                            return $query->withCount('releases')
                                ->orderByDesc('releases_count');
                        }
                        
                        return $query->withSum('revenues', 'revenue_amount')
                            ->orderByDesc('revenues_sum_revenue_amount');
                    }),
            ])
            ->poll('10s');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaderboards::route('/'),
        ];
    }
}