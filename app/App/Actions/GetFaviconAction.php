<?php

namespace DDD\App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Base\Files\Actions\StoreFileFromUrlAction;
use DDD\App\Services\Favicon\FaviconInterface;

class GetFaviconAction
{
    use AsAction;

    public function __construct(
        protected FaviconInterface $favicon,
    ) {}

    function handle(string $url)
    {
        try {
            $faviconUrl = $this->favicon->take($url, 'small');

            $file = StoreFileFromUrlAction::run($faviconUrl, 'favicons');
    
            return $file;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
