<?php

namespace WeDevelop\ElementalGrid\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;

class ElementalPageExtension extends DataExtension
{
    private static bool $elemental_keep_content_field = true;

    private static array $db = [
        'UseElementalGrid' => 'Boolean',
    ];

    private static array $defaults = [
        'UseElementalGrid' => true,
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        parent::updateCMSFields($fields);

        if ($this->owner->UseElementalGrid) {
            $fields->removeByName('Content');
            $insertBefore = 'ElementalArea';
        } else {
            $fields->removeByName('ElementalArea');
            $insertBefore = 'Content';
        }

        $fields->insertBefore(
            $insertBefore,
            CheckboxField::create('UseElementalGrid', _t(__CLASS__ . '.USE_ELEMENTAL_GRID', 'Use grid on this page'))
                ->setDescription(_t(__CLASS__ . '.USE_ELEMENTAL_GRID_DESCRIPTION', 'Make sure to save this page right after changing this setting'))
        );
    }

    public function updateAnchorsOnPage(array &$anchors): void
    {
        if ($this->getOwner()->ElementalArea()->exists()) {
            $area = $this->getOwner()->ElementalArea();
            $elementalAnchors = [];

            if ($area instanceof ElementalArea) {
                $area->Elements()->each(function (BaseElement $element) use (&$elementalAnchors) {
                    if ($element->HTML) {
                        $elementalAnchors = array_merge($elementalAnchors, $this->getAnchorsInContent($element->HTML));
                    }

                    $elementalAnchors[] = $element->getAnchor();
                });
            }

            $anchors = array_merge($anchors, $elementalAnchors);
        }
    }

    public function getAnchorsInContent(string $content): array
    {
        $parseSuccess = preg_match_all(
            "/\\s+(name|id)\\s*=\\s*([\"'])([^\\2\\s>]*?)\\2|\\s+(name|id)\\s*=\\s*([^\"']+)[\\s +>]/im",
            $content,
            $matches
        );

        $anchors = [];

        if ($parseSuccess >= 1) {
            $anchors = array_values(array_unique(array_filter(
                array_merge($matches[3], $matches[5])
            )));
        }

        return $anchors;
    }
}
