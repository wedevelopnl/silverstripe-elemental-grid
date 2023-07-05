<?php

namespace WeDevelop\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;
use DNADesign\Elemental\Models\BaseElement;

final class ElementRowController extends ElementController
{
    private bool $isLastRow = false;

    private bool $isFirstRow = false;

    private BaseElement $previousRow;

    public function forTemplate(): ?string
    {
        return $this->element->forTemplate(false);
    }

    public function getIsLastRow(): bool
    {
        return $this->isLastRow;
    }

    public function setIsLastRow(bool $value): void
    {
        $this->isLastRow = $value;
    }

    public function getIsFirstRow(): bool
    {
        return $this->isFirstRow;
    }

    public function setIsFirstRow(bool $value): void
    {
        $this->isFirstRow = $value;
    }

    public function getPreviousRow(): ?BaseElement
    {
        return $this->previousRow;
    }

    public function setPreviousRow(BaseElement $value): void
    {
        $this->previousRow = $value;
    }
}
