<?php

namespace DDD\Domain\Files\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Storage;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Files\File;

class StoreFileFromUrlAction
{
    use AsAction;
    
    function handle(Organization $organization, String $url)
    {
        $disk = config('filesystems.default');

        $fileInfo = pathinfo($url); // Get parts of the URL
        $title = $fileInfo['filename']; // dog
        $filename = $fileInfo['basename']; // dog.jpg
        $extension = $fileInfo['extension']; // jpg

        try {
            Storage::put($filename, file_get_contents($url));

            $file = File::create([
                'organization_id' => $organization->id,
                'title' => $title,
                'filename' => $filename,
                'extension' => $extension,
                'disk' => $disk,
            ]);

            return $file;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}