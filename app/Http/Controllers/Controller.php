<?php

namespace App\Http\Controllers;
use App\Services\SpotifyService;
use Illuminate\Http\Request;

abstract class Controller
{
    //
}

class MusicAnalyticsController extends Controller
{
    public function showPopularityByISRC($isrc)
    {
        $spotifyService = new SpotifyService();
        $popularity = $spotifyService->getTrackStreamsByISRC($isrc);

        if ($popularity !== null) {
            return response()->json(['popularity' => $popularity]);
        } else {
            return response()->json(['error' => 'Track not found or unable to retrieve popularity.'], 404);
        }
    }
}
