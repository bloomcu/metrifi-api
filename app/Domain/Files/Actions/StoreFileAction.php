<?php

namespace DDD\Domain\Files\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
// use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Files\File;

class StoreFileAction
{
    use AsAction;
    
    function handle(Organization $organization, $newFile, $folderId = null)
    {
        $disk = config('filesystems.default');
        $title = pathinfo($newFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $newFile->getClientOriginalExtension();
        $filename = $newFile->store();

        // Check if extension can be resized
        // if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // $optimized = Image::read($newFile->getRealPath())->scale(width: 2000);
            // Storage::disk($disk)->put($filename, (string) $optimized->encode());
        // } else {
            // $newFile->store();
        // }

        $file = File::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'filename' => $filename,
            'extension' => $extension,
            'size' => 0,
            'disk' => $disk,
        ]);

        return $file;
    }
}