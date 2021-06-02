<?php

namespace BiffBangPow\Stackpath\Task;

use BiffBangPow\Stackpath\Service\Stackpath;
use SilverStripe\Dev\BuildTask;

class FlushCDN extends BuildTask
{

    public function run($request)
    {
        echo "\nFlushing CDN... ";
        Stackpath::doFlush();
        echo "done.\n";
    }
}
