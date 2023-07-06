<?php

namespace WeDevelop\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\View\Requirements;

final class ElementContentController extends ElementController
{
    public function init(): void
    {
        if ($this->element instanceof ElementContent && $this->data()->MediaType === 'video') {
            Requirements::javascript('wedevelopnl/silverstripe-elemental-grid:client/dist/js/video.js');
            Requirements::css('wedevelopnl/silverstripe-elemental-grid:client/dist/styles/video.css');
        }

        parent::init();
    }
}
