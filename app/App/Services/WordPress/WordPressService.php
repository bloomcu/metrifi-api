<?php

namespace DDD\App\Services\WordPress;

use Illuminate\Support\Facades\Http;

class WordPressService
{
    public function createPost(array $post)
    {
      $wordpressUrl = 'http://localhost:10005/wp-json/wp/v2/posts';
      $username = 'heyharmon';
      $appPassword = 'OYnX 107C XAfQ KSA9 ct7x jshS';

      $response = Http::withBasicAuth($username, $appPassword)
          ->post($wordpressUrl, $post);

      if ($response->successful()) {
          return response()->json(['message' => 'Post created successfully', 'post' => $response->json()]);
      } else {
          return response()->json(['error' => 'Failed to create post', 'details' => $response->json()], $response->status());
      }
    }
}
