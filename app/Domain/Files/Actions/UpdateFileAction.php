<?php

namespace DDD\Domain\Files\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Storage;
use DDD\Domain\Files\File;

class UpdateFileAction
{
    use AsAction;
    
    function handle(File $file, $newFile)
    {
        $disk = config('filesystems.default');

        $title = pathinfo($newFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename =  $newFile->store();

        Storage::disk($disk)->delete($file->filename);

        $file->update([
            'title' => $title,
            'filename' => $filename,
            'extension' => $newFile->getClientOriginalExtension(),
            'size' => $newFile->getSize(),
        ]);

        return $file;
    }
}