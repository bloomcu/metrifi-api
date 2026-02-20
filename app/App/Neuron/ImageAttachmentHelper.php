<?php

namespace DDD\App\Neuron;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;

class ImageAttachmentHelper
{
    /**
     * Download an image URL and return the raw base64 string for storage.
     * Retries on failure with exponential backoff.
     */
    public static function downloadToBase64(string $url, int $maxAttempts = 3, int $timeout = 60): ?string
    {
        $client = new Client(['timeout' => $timeout]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->get($url);
                return base64_encode($response->getBody()->getContents());
            } catch (\Throwable $e) {
                Log::warning("Image download attempt {$attempt}/{$maxAttempts} failed for URL: {$e->getMessage()}");

                if ($attempt < $maxAttempts) {
                    sleep(pow(2, $attempt));
                }
            }
        }

        return null;
    }

    /**
     * Create a Neuron Image attachment from a stored base64 string.
     */
    public static function fromBase64(string $base64, string $mediaType = 'image/jpeg'): Image
    {
        return new Image($base64, AttachmentContentType::BASE64, $mediaType);
    }

    /**
     * Detect a reasonable media type from a URL's query params or path.
     */
    public static function detectMediaType(string $url): string
    {
        if (str_contains($url, 'format=jpeg') || str_contains($url, 'format=jpg')) {
            return 'image/jpeg';
        }

        if (str_contains($url, 'format=png')) {
            return 'image/png';
        }

        if (str_contains($url, 'format=webp')) {
            return 'image/webp';
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
