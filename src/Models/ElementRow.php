<?php

namespace WeDevelop\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\GraphQL\Schema\Field\Field;
use WeDevelop\ElementalGrid\Controllers\ElementRowController;
use WeDevelop\ElementalGrid\Extensions\BaseElementExtension;

/***
 * @method BaseElement|BaseElementExtension getOwner()
 * @property bool $IsFluid
 * @property bool $CustomSectionClass
 */
class ElementRow extends BaseElement
{
    private static string $icon = 'font-icon-menu';

    private static string $table_name = 'ElementRow';

    private static string $singular_name = 'row';

    private static string $plural_name = 'rows';

    private static string $description = 'Row element';

    private static string $controller_class = ElementRowController::class;

    /**
     * @var array<string, string>
     */
    private static array $db = [
        'IsFluid' => 'Boolean',
        'CustomSectionClass' => 'Varchar(255)',
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName([
                'Column',
                'TitleTag',
                'ShowTitle',
                'TitleClass'
            ]);

            $fields->renameField('ExtraClass', _t(__CLASS__ . '.CUSTOM_ROW_CLASSES', 'Custom row classes'));

            $fields->addFieldsToTab(
                'Root.Settings',
                [
                    TextField::create('CustomSectionClass', _t(__CLASS__ . '.CUSTOM_SECTION_CLASSES', 'Custom section classes')),
                ]
            );

            if (!$fields->fieldPosition('FullWidth')) {
                $fields->addFieldsToTab(
                    'Root.Main',
                    [
                        CheckboxField::create('IsFluid', _t(__CLASS__ . '.IS_FLUID', 'The row uses the full width of the page')),
                    ]
                );
            }
        });

        return parent::getCMSFields();
    }

    public function getType(): string
    {
        return _t(__CLASS__ . '.LABEL', 'Row');
    }

    public function getRowClasses(): string
    {
        $classes[] = $this->getCSSFramework()->getRowClasses();

        $this->extend('updateRowClasses', $classes);

        if ($this->getOwner()->ExtraClass) {
            $classes[] = $this->getOwner()->ExtraClass;
        }

        return implode(' ', $classes);
    }

    public function getSectionClasses(): string
    {
        $classes[] = 'section';

        $this->extend('updateSectionClasses', $classes);

        if ($this->CustomSectionClass) {
            $classes[] = $this->CustomSectionClass;
        }

        return implode(' ', $classes);
    }

    public function getContainerClasses(): string
    {
        $classes[] = $this->getCSSFramework()->getContainerClass((bool)$this->IsFluid);

        $this->extend('updateContainerClasses', $classes);

        return implode(' ', $classes);
    }
}
