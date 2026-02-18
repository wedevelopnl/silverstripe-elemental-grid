<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Extensions;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;
use WeDevelop\ElementalGrid\Extensions\ElementalGridLeftAndMainExtension;

#[CoversClass(ElementalGridLeftAndMainExtension::class)]
final class ElementalGridLeftAndMainExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testBlocksStockElementalJsBundle(): void
    {
        $extension = new ElementalGridLeftAndMainExtension();
        $extension->onAfterInit();

        // Requirements::block() resolves module paths via ModuleResourceLoader
        $expectedKey = ModuleResourceLoader::singleton()->resolvePath(
            'dnadesign/silverstripe-elemental:client/dist/js/bundle.js',
        );

        $blocked = Requirements::backend()->getBlocked();
        $this->assertArrayHasKey($expectedKey, $blocked);
    }

    public function testBlocksStockElementalCssBundle(): void
    {
        $extension = new ElementalGridLeftAndMainExtension();
        $extension->onAfterInit();

        $expectedKey = ModuleResourceLoader::singleton()->resolvePath(
            'dnadesign/silverstripe-elemental:client/dist/styles/bundle.css',
        );

        $blocked = Requirements::backend()->getBlocked();
        $this->assertArrayHasKey($expectedKey, $blocked);
    }
}
