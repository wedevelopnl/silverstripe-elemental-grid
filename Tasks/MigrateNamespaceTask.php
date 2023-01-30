<?php

namespace WeDevelop\ElementalGrid\Tasks;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use WeDevelop\ElementalGrid\Models\ElementRow;

class MigrateNamespaceTask extends BuildTask
{
    protected $title = 'Elemental grid namespace migration';

    protected $description = 'Migrate elemental grid TheWebmen namespace to WeDevelop namespace';

    private static $segment = 'migrate-elemental-grid-namespace';

    public function run($request)
    {
        $elements = BaseElement::get()
            ->where([
                'ClassName' => 'TheWebmen\ElementalGrid\Models\ElementRow'
            ]);

        $counter = 0;
        $totalElements = $elements->count();

        print_r(sprintf("Starting migration of %s elements\n\n", $totalElements));

        /** @var BaseElement $element */
        foreach ($elements as $element) {
            $isPublished = $element->isPublished();

            $element->setField('ClassName', 'WeDevelop\ElementalGrid\Models\ElementRow');
            $element->write();

            if ($isPublished) {
                $element->publishSingle();
            }

            $counter++;

            print_r(sprintf("Migrated %s of %s elements\n", $counter, $totalElements));
        }

        print_r(sprintf("\n\nMigration done for %s elements!", $counter));
    }
}