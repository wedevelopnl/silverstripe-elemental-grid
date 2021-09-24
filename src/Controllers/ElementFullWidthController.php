<?php

namespace Webmen\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;

class ElementFullWidthController extends ElementController
{
    private bool $needsClosing = false;
    private bool $after = false;

    public function forTemplate(): string
    {
        return $this->renderWith(
            [
                'type' => 'Layout',
                'Webmen\\ElementalGrid\\ElementFullWidthHolder',
            ]
        );
    }

    public function getNeedsClosing(): bool
    {
        return $this->needsClosing;
    }

    public function setNeedsClosing(bool $needClosing): void
    {
        $this->needsClosing = $needClosing;
    }

    public function getAfter(): bool
    {
        return $this->after;
    }

    public function setAfter(bool $after): void
    {
        $this->after = $after;
    }
}
