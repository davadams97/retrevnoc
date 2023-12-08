<?php

namespace App\Http\Controllers;

use Fuse\Fuse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $songsToAdd = [];

        foreach ($request['name'] as $song) {
            $result = $this->findSongFromYTMusic($song);
            // don't do anything if result is empty
            $songsToAdd[] = $result[0]['item'];
        }

        $playlistId = $this->createPlaylist($request['title']);

        $songsToAddIds = array_map(fn ($song) => $song['videoId'], $songsToAdd);

        info($songsToAddIds);

        // foreach ($songsToAdd as $song) {
        // info($song);
        $this->addSongToPlaylist($songsToAddIds, $playlistId);
        // }

        // delete playlist if fail

        // TODO: check for explicit
        // TODO: check for casing
        // TODO: check for song name and artist
    }

    private function createPlaylist($title)
    {
        return Http::withHeaders(['Content-Type' => 'application/json'])->post(env('YOUTUBE_API').'playlists', [
            'title' => $title,
        ]);

        // Handle error
    }

    private function findSongFromYTMusic($songName)
    {
        $filter = [
            'keys' =>
            // ['name' => 'title', 'getFn' => fn ($res) => $res['title']],
            // ['name' => 'album', 'getFn' => fn ($res) => $res['album']['name']],
            ['title', 'artists.name', 'album.name'],
            'shouldSort' => true,
        ];

        $searchResults = Http::get(env('YOUTUBE_API').'search', [
            'query' => $songName,
        ])->json();

        $fuse = new Fuse($searchResults, $filter);

        $matchedResult = $fuse->search($songName, ['limit' => 1]);

        // $matchedResult = array_filter(
        //     $searchResults,
        //     fn ($result) => strtolower($result['title']) === strtolower($songName)
        // )[0];

        return $matchedResult;
        // Handle error
    }

    private function addSongToPlaylist($videoIds, $playlistId)
    {
        Http::withHeaders(['Content-Type' => 'application/json'])->post(env('YOUTUBE_API').'playlists/'.$playlistId.'/add-songs', [
            'videoIds' => $videoIds,
        ]);
    }
}
