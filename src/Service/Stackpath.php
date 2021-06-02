<?php

namespace BiffBangPow\Stackpath\Service;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Flushable;

class Stackpath implements Flushable
{
    use Configurable;

    /**
     * @config
     * @var boolean $enable_log
     */
    private static $enable_log = false;

    /**
     * @config
     * @var boolean $always_flush
     */
    private static $always_flush = true;

    /**
     * Triggers the flush from the Flushable implementation
     * if the config is set up to do so.
     */
    public static function flush()
    {
        if (self::config()->get('always_flush') === true) {
            self::doFlush();
        }

        return true;
    }

    /**
     * Trigger a CDN flush
     * @todo - Enable logging in this method
     */
    public static function doFlush()
    {
        $spConnector = new StackpathConnector();
        $spConnector->purgeCache();

        if (self::config()->get('enable_log') === true) {
            //Log the action, reponse, etc.
        }
    }
}
