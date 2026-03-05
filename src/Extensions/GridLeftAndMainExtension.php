<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Blocks stock elemental JS and CSS bundles so they don't conflict
 * with our React-based grid editor.
 *
 * @extends Extension<\SilverStripe\Admin\LeftAndMain>
 */
class GridLeftAndMainExtension extends Extension
{
    public function onAfterInit(): void
    {
        Requirements::block('dnadesign/silverstripe-elemental:client/dist/js/bundle.js');
        Requirements::block('dnadesign/silverstripe-elemental:client/dist/styles/bundle.css');
    }
}
