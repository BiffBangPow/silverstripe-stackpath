<?php

namespace BiffBangPow\Stackpath\Extension;

use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'StackpathBearer' => 'Text',
        'StackpathTokenExpires' => 'Int'
    ];
}
