<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Contract\ContainerInterface;

/**
 * Abstract base for structural container elements (Section, Row, Column).
 *
 * Containers define a typed has_many to their specific children and
 * implement the ContainerInterface for polymorphic container operations.
 */
abstract class ContainerElement extends GridElement implements ContainerInterface
{
    private static string $table_name = 'ContainerElement';

    /**
     * Return the typed has_many list of child elements.
     * Subclasses define the concrete relationship (e.g., Section->Rows, Row->Columns).
     *
     * @return HasManyList<GridElement>
     */
    abstract public function getChildren(): HasManyList;

    /** Human-readable child type name for summaries (e.g., "row", "column", "element"). */
    abstract public function getChildTypeName(): string;

    #[\Override]
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
}
