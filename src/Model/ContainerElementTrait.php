<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Value\ContainerType;

/**
 * Shared behavior for structural container elements (Section, Row, Column).
 *
 * Provides default implementations for ContainerInterface methods that
 * depend on getChildren() and getChildTypeName(), which each container
 * must define itself.
 *
 * This is a trait rather than a base class because SilverStripe's ORM
 * cannot handle intermediate DataObject classes that add no DB columns:
 * abstract classes crash TableBuilder, and concrete classes create
 * empty tables with table-name conflicts.
 */
trait ContainerElementTrait
{
    /** @return HasManyList<GridElement> */
    abstract public function getChildren(): HasManyList;

    abstract public function getChildTypeName(): string;

    abstract public function getContainerType(): ContainerType;

    public function hasChildren(): bool
    {
        return $this->getChildren()->exists();
    }

    /** Formatted child count, e.g. "3 rows" or "1 column". */
    public function getChildCountSummary(): string
    {
        $count = $this->getChildren()->count();
        $typeName = $this->getChildTypeName();

        return sprintf('%d %s', $count, $count === 1 ? $typeName : $typeName . 's');
    }

    #[\Override]
    public function getSummary(): string
    {
        return $this->getChildCountSummary();
    }

    /** Short class name for CSS class generation in templates. */
    public function getSimpleClassName(): string
    {
        return ClassInfo::shortName(static::class);
    }
}
