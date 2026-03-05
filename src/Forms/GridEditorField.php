<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Forms;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Lightweight form field that serves as the React mount point for the
 * grid editor. Replaces stock {@see \DNADesign\Elemental\Forms\ElementalAreaField}
 * via Injector config — when elemental calls ElementalAreaField::create(),
 * the Injector instantiates this class instead.
 *
 * Renders a bare `<div>` with data attributes that the entwine bridge
 * reads to mount the React application.
 */
class GridEditorField extends FormField
{
    private ElementalArea $area;

    /** @var list<class-string> */
    private array $blockTypes;

    /**
     * @param list<class-string> $blockTypes Allowed element types (passed by
     *   ElementalAreasExtension via Injector; stored for future use in the editor UI)
     */
    public function __construct(string $name, ElementalArea $area, array $blockTypes = [])
    {
        $this->area = $area;
        $this->blockTypes = $blockTypes;

        parent::__construct($name);

        $this->addExtraClass('grid-editor__container no-change-track');
    }

    /** @return list<class-string> */
    public function getBlockTypes(): array
    {
        return $this->blockTypes;
    }

    public function getArea(): ElementalArea
    {
        return $this->area;
    }

    /** @return array<string, mixed> */
    public function getSchemaDataDefaults(): array
    {
        /** @var array<string, mixed> $schemaData */
        $schemaData = parent::getSchemaDataDefaults();

        $area = $this->getArea();
        $page = $area->getOwnerPage();

        $schemaData['grid-area-id'] = (int) $area->ID;
        $schemaData['grid-page-id'] = $page !== null ? (int) $page->ID : null;

        return $schemaData;
    }

    /**
     * No-op: element mutations are handled by the API controller, not
     * the CMS form. The base FormField::saveInto() would call
     * setCastedField('ElementalArea', null) — nullifying the has_one —
     * because the grid editor submits no POST data for this field.
     */
    public function saveInto(DataObjectInterface $record): void
    {
        // Intentionally empty
    }

    public function performReadonlyTransformation(): LiteralField
    {
        return LiteralField::create($this->name, '');
    }
}
