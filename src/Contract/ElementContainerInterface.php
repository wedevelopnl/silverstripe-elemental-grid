<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

use DNADesign\Elemental\Models\ElementalArea;

interface ElementContainerInterface
{
    public function getChildArea(): ElementalArea;

    public function hasChildren(): bool;

    public function getContainerType(): ContainerType;
}
