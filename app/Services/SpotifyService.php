<?php
// app/Services/SpotifyService.php
// app/Services/SpotifyService.php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SpotifyService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.spotify.com/v1/']);
    }

    public function authenticate()
    {
        try {
            $response = $this->client->post('https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('SPOTIFY_CLIENT_ID') . ':' . env('SPOTIFY_CLIENT_SECRET')),
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $token = json_decode($response->getBody()->getContents(), true)['access_token'];
            return $token;
        } catch (RequestException $e) {
            return null;
        }
    }

    public function getTrackStreamsByISRC($isrc)
    {
        $token = $this->authenticate();

        if (!$token) {
            return null;
        }

        try {
            $response = $this->client->get('search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'query' => [
                    'q' => "isrc:$isrc",
                    'type' => 'track',
                    'limit' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Mengembalikan popularitas sebagai indikasi jumlah stream
            return $data['tracks']['items'][0]['popularity'] ?? null;
        } catch (RequestException $e) {
            return null;
        }
    }
}
