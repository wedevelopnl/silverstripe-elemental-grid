<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use SilverStripe\ORM\HasManyList;

interface ContainerInterface
{
    public function getChildren(): HasManyList;

    public function hasChildren(): bool;

    public function getContainerType(): ContainerType;
}
