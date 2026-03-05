<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Base class for leaf content elements that render visible content.
 *
 * Content elements sit inside columns and produce template output.
 * They are never containers — they do not hold child elements.
 */
class ContentElement extends GridElement
{
    private static string $table_name = 'ContentElement';

    /** Whether content of this type should be included in search indexes. */
    private static bool $search_indexable = true;

    /** Render this element using the SilverStripe template engine. */
    public function forTemplate(): string
    {
        $templates = $this->getRenderTemplates();

        /** @var DBHTMLText $result */
        $result = $this->renderWith($templates);

        return (string) $result;
    }

    /**
     * Build the template hierarchy for rendering.
     * Walks the class ancestry to provide fallback templates.
     *
     * @return list<string>
     */
    public function getRenderTemplates(string $suffix = ''): array
    {
        $templates = [];
        $class = static::class;

        while ($class !== self::class && $class !== GridElement::class && $class !== false) {
            $templates[] = str_replace('\\', '/', $class) . $suffix;
            $class = get_parent_class($class);
        }

        return $templates;
    }

    /** Whether this element type should be indexed for site search. */
    public function getSearchIndexable(): bool
    {
        return (bool) static::config()->get('search_indexable');
    }
}
