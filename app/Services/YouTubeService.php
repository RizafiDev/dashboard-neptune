<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('YOUTUBE_API_KEY');
    }

    public function searchVideos($query)
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'key' => $this->apiKey,
            'maxResults' => 5, // Atur jumlah hasil pencarian yang diinginkan
        ]);

        if ($response->successful()) {
            return $response->json()['items'] ?? [];
        }

        return [];
    }

    public function getViewCountByVideoId($videoId)
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'statistics',
            'id' => $videoId,
            'key' => $this->apiKey,
        ]);

        if ($response->successful()) {
            return $response->json()['items'][0]['statistics']['viewCount'] ?? 0;
        }

        return 0;
    }

    public function getTrackViews($upc, $artistName, $trackTitle)
    {
        $query = $trackTitle . ' ' . $artistName;
        $videos = $this->searchVideos($query);

        // Jika video ditemukan, ambil videoId dari hasil pertama
        if (!empty($videos)) {
            $videoId = $videos[0]['id']['videoId'];
            return $this->getViewCountByVideoId($videoId);
        }

        return 0; // Jika tidak ada video ditemukan
    }
}

