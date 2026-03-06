<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Lightweight form field that serves as the React mount point for the
 * grid editor. Renders a bare `<div>` with data attributes that the
 * entwine bridge reads to mount the React application.
 */
class GridEditorField extends FormField
{
    private int $pageId;

    private string $zone;

    public function __construct(string $name, int $pageId, string $zone = 'main')
    {
        $this->pageId = $pageId;
        $this->zone = $zone;

        parent::__construct($name);

        $this->addExtraClass('grid-editor__container no-change-track');
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    /** @return array<string, mixed> */
    public function getSchemaDataDefaults(): array
    {
        /** @var array<string, mixed> $schemaData */
        $schemaData = parent::getSchemaDataDefaults();

        $schemaData['grid-page-id'] = $this->pageId;
        $schemaData['grid-zone'] = $this->zone;

        return $schemaData;
    }

    /**
     * No-op: element mutations are handled by the API controller, not
     * the CMS form. The base FormField::saveInto() would call
     * setCastedField on the record — which we don't want because the
     * grid editor submits no POST data for this field.
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
