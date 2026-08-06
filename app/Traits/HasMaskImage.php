<?php

namespace App\Traits;

use App\Helper\Common;
use App\Models\GlobalSetting;

trait HasMaskImage
{

    private function generateMaskedImageAppUrl($path): string
    {
        $filePath = Common::encryptDecrypt($path) . '_masked.png';

        return url()->temporarySignedRoute(
            'file.getFile',
            now()->addDays(GlobalSetting::SIGNED_ROUTE_EXPIRY),
            ['type' => 'image', 'path' => $filePath]
        );
    }

}
