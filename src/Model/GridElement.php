<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * Abstract base for all grid elements (containers and content).
 *
 * Uses a polymorphic has_one (ParentID + ParentClass) so elements can
 * live under any DataObject — a page, a container, or a future zone owner.
 *
 * @property string $Title
 * @property bool $ShowTitle
 * @property int $Sort
 * @property string $ExtraClass
 * @property string $Style
 * @property string $Zone
 * @property int $ParentID
 * @property string $ParentClass
 * @method DataObject Parent()
 * @mixin Versioned
 */
abstract class GridElement extends DataObject
{
    private static string $table_name = 'GridElement';

    /** @var array<string, string> */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'ShowTitle' => 'Boolean',
        'Sort' => 'Int',
        'ExtraClass' => 'Varchar(255)',
        'Style' => 'Varchar(255)',
        'Zone' => 'Varchar(50)',
    ];

    /** @var array<string, string> */
    private static array $has_one = [
        'Parent' => DataObject::class,
    ];

    /** @var array<string, string> */
    private static array $defaults = [
        'ShowTitle' => '0',
    ];

    /** @var list<class-string> */
    private static array $extensions = [
        Versioned::class,
    ];

    /** @var string */
    private static string $default_sort = '"Sort" ASC';

    /** @var array<string, array<string, string>> */
    private static array $indexes = [
        'Sort' => [
            'type' => 'index',
            'columns' => ['Sort'],
        ],
        'ParentZone' => [
            'type' => 'index',
            'columns' => ['ParentID', 'ParentClass', 'Zone'],
        ],
    ];

    /** Human-readable element type identifier (e.g., "Section", "Row", "Text"). */
    abstract public function getType(): string;

    /** Anchor-safe identifier for linking within a page. */
    public function getAnchor(): string
    {
        return sprintf('grid-element-%d', $this->ID);
    }

    /** Class name with namespace separators replaced for safe use as identifiers. */
    public function getTypeName(): string
    {
        return str_replace('\\', '-', static::class);
    }

    /** Summary text for CMS grid views. Override in subclasses. */
    public function getSummary(): string
    {
        return '';
    }

    /**
     * Block schema data consumed by the CMS editor React components.
     *
     * @return array<string, mixed>
     */
    public function getBlockSchema(): array
    {
        $schema = [
            'id' => $this->ID,
            'typeName' => $this->getTypeName(),
            'type' => $this->getType(),
            'title' => $this->Title ?? '',
            'summary' => $this->getSummary(),
        ];

        return array_merge($schema, $this->provideBlockSchema());
    }

    /**
     * Extension point for subclasses to add extra block schema fields.
     *
     * @return array<string, mixed>
     */
    protected function provideBlockSchema(): array
    {
        return [];
    }

    /**
     * Walk the Parent chain until reaching a SiteTree (page) or a non-GridElement owner.
     * Returns null if no page is found.
     */
    public function getPage(): ?DataObject
    {
        $parent = $this->Parent();

        if (!$parent->exists()) {
            return null;
        }

        if ($parent instanceof SiteTree) {
            return $parent;
        }

        if ($parent instanceof self) {
            return $parent->getPage();
        }

        // Parent is a non-GridElement DataObject — treat it as the owning "page"
        return $parent;
    }

    /**
     * @param Member|null $member
     * @return bool|null
     */
    public function canView($member = null): bool|null
    {
        $page = $this->getPage();

        return $page !== null ? (bool) $page->canView($member) : (bool) Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * @param Member|null $member
     * @return bool|null
     */
    public function canEdit($member = null): bool|null
    {
        $page = $this->getPage();

        return $page !== null ? (bool) $page->canEdit($member) : (bool) Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * @param Member|null $member
     * @return bool|null
     */
    public function canDelete($member = null): bool|null
    {
        $page = $this->getPage();

        return $page !== null ? (bool) $page->canDelete($member) : (bool) Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * @param Member|null $member
     * @param array<string, mixed> $context
     * @return bool|null
     */
    public function canCreate($member = null, $context = []): bool|null
    {
        return (bool) Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Sets Sort to one past the current maximum for this parent + zone
     * combination when no explicit Sort has been assigned.
     */
    protected function ensureSortSet(): void
    {
        if ($this->Sort > 0) {
            return;
        }

        $max = static::get()
            ->filter([
                'ParentID' => $this->ParentID,
                'ParentClass' => $this->ParentClass,
                'Zone' => $this->Zone ?: '',
            ])
            ->max('Sort');

        $this->Sort = (is_numeric($max) ? (int) $max : 0) + 1;
    }

    #[\Override]
    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $this->ensureSortSet();
    }
}
