<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Fixture;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

/**
 * Test spy that records whether onAfterWrite was propagated through the
 * parent chain. Applied temporarily during tests to verify that concrete
 * onAfterWrite implementations call parent::onAfterWrite().
 */
class OnAfterWriteSpy extends Extension implements TestOnly
{
    public static bool $called = false;

    protected function onAfterWrite(): void
    {
        self::$called = true;
    }
}
