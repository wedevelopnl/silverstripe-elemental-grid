<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Model\GridElement;

interface ContainerInterface
{
    /** @return HasManyList<GridElement> */
    public function getChildren(): HasManyList;

    public function hasChildren(): bool;

    public function getContainerType(): ContainerType;
}
