<?php

// namespace App\Services;

// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Cache;

// class YouTubeService
// {
//     protected $apiKey;

//     public function __construct()
//     {
//         $this->apiKey = env('YOUTUBE_API_KEY');
//     }

//     // Mencari video berdasarkan query dan menggunakan cache
//     public function searchVideos($query)
//     {
//         // Cek apakah sudah ada hasil pencarian di cache
//         $cacheKey = "youtube_search_{$query}";
//         $cachedResult = Cache::get($cacheKey);

//         if ($cachedResult) {
//             return $cachedResult;  // Mengembalikan hasil dari cache jika ada
//         }

//         // Jika tidak ada cache, lakukan request API dan cache hasilnya
//         $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
//             'part' => 'snippet',
//             'q' => $query,
//             'type' => 'video',
//             'key' => $this->apiKey,
//             'maxResults' => 2,
//         ]);

//         if ($response->successful()) {
//             $result = $response->json()['items'] ?? [];
//             // Simpan hasil pencarian di cache selama 1 jam
//             Cache::put($cacheKey, $result, 3600);  // 3600 detik = 1 jam
//             return $result;
//         }

//         return [];
//     }

//     // Mendapatkan jumlah tampilan video berdasarkan ID dan menggunakan cache
//     public function getViewCountByVideoId($videoId)
//     {
//         // Cache key untuk view count video
//         $cacheKey = "youtube_video_views_{$videoId}";
//         $cachedViews = Cache::get($cacheKey);

//         if ($cachedViews) {
//             return $cachedViews;  // Mengembalikan view count dari cache jika ada
//         }

//         // Jika tidak ada cache, lakukan request API untuk mendapatkan view count
//         $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
//             'part' => 'statistics',
//             'id' => $videoId,
//             'key' => $this->apiKey,
//         ]);

//         if ($response->successful()) {
//             $viewCount = $response->json()['items'][0]['statistics']['viewCount'] ?? 0;
//             // Simpan jumlah tampilan di cache selama 1 jam
//             Cache::put($cacheKey, $viewCount, 3600);  // 3600 detik = 1 jam
//             return $viewCount;
//         }

//         return 0;
//     }

//     // Mendapatkan jumlah tampilan berdasarkan UPC, artistName, dan trackTitle
//     public function getTrackViews($artistName, $trackTitle)
//     {
//         // Membuat query pencarian untuk track
//         $query = $trackTitle . ' ' . $artistName;
//         $videos = $this->searchVideos($query);

//         // Jika video ditemukan, ambil videoId dari hasil pertama
//         if (!empty($videos)) {
//             $videoId = $videos[0]['id']['videoId'];
//             return $this->getViewCountByVideoId($videoId);
//         }

//         return 0;  // Jika tidak ada video ditemukan
//     }
// }



namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Release;
use Carbon\Carbon;

class YouTubeService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('YOUTUBE_API_KEY');
    }

    public function getTrackViews($artistName, $trackTitle, $releaseId)
    {
        // Ambil record release dari database
        $release = Release::find($releaseId);

        if ($release) {
            // Cek kapan terakhir diperbarui
            $lastUpdated = $release->youtube_stream_updated_at;
            $now = Carbon::now();

            // Jika data belum kadaluarsa (misal kurang dari 4 jam), gunakan data dari database
            if ($lastUpdated && $now->diffInHours($lastUpdated) < 4 && $release->youtube_stream !== null) {
                return $release->youtube_stream;
            }

            // Jika data kadaluarsa, perbarui dari API
            $query = $trackTitle . ' ' . $artistName;
            $videos = $this->searchVideos($query);

            if (!empty($videos)) {
                $videoId = $videos[0]['id']['videoId'];
                $viewCount = $this->getViewCountByVideoId($videoId);

                // Simpan jumlah tampilan dan waktu diperbarui ke database
                $release->youtube_stream = $viewCount;
                $release->youtube_stream_updated_at = $now;
                $release->save();

                return $viewCount;
            }
        }

        return 0;  // Jika tidak ada video ditemukan atau record tidak ditemukan
    }

    // Mencari video berdasarkan query dan menggunakan cache
    public function searchVideos($query)
    {
        $cacheKey = "youtube_search_{$query}";
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'key' => $this->apiKey,
            'maxResults' => 2,
        ]);

        if ($response->successful()) {
            $result = $response->json()['items'] ?? [];
            Cache::put($cacheKey, $result, 3600);
            return $result;
        }

        return [];
    }

    // Mendapatkan jumlah tampilan video berdasarkan ID dan menggunakan cache
    public function getViewCountByVideoId($videoId)
    {
        $cacheKey = "youtube_video_views_{$videoId}";
        $cachedViews = Cache::get($cacheKey);

        if ($cachedViews) {
            return $cachedViews;
        }

        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'statistics',
            'id' => $videoId,
            'key' => $this->apiKey,
        ]);

        if ($response->successful()) {
            $viewCount = $response->json()['items'][0]['statistics']['viewCount'] ?? 0;
            Cache::put($cacheKey, $viewCount, 3600);
            return $viewCount;
        }

        return 0;
    }
}
