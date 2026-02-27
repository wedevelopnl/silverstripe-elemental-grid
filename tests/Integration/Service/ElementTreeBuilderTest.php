<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Service;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;
use WeDevelop\ElementalGrid\Model\ElementNode;
use WeDevelop\ElementalGrid\Service\ElementTreeBuilder;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

#[CoversClass(ElementTreeBuilder::class)]
final class ElementTreeBuilderTest extends SapphireTest
{
    protected static $fixture_file = __DIR__ . '/../Fixture/ElementTreeTest.yml';

    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
    }

    /**
     * @return array<string, list<ElementNode>>
     */
    private function buildTree(): array
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        /** @var ElementTreeBuilder $builder */
        $builder = Injector::inst()->get(ElementTreeBuilder::class);

        return $builder->buildForPage($page);
    }

    // ---- Full tree structure ----

    public function testTreeShapeMatchesFixtureHierarchy(): void
    {
        $tree = $this->buildTree();

        $this->assertArrayHasKey('ElementalArea', $tree);

        // 2 sections at root
        $sections = $tree['ElementalArea'];
        $this->assertCount(2, $sections);
        $this->assertSame('First Section', $sections[0]->title);
        $this->assertSame('Second Section', $sections[1]->title);
        $this->assertSame($this->idFromFixture(ElementSection::class, 'section1'), $sections[0]->id);
        $this->assertSame($this->idFromFixture(ElementSection::class, 'section2'), $sections[1]->id);

        // Section 1 → 3 rows
        $rows = $sections[0]->children;
        $this->assertCount(3, $rows);
        $this->assertSame('First Row', $rows[0]->title);
        $this->assertSame('Second Row', $rows[1]->title);
        $this->assertSame('Empty Row', $rows[2]->title);
        $this->assertSame($this->idFromFixture(ElementRow::class, 'row1'), $rows[0]->id);
        $this->assertSame($this->idFromFixture(ElementRow::class, 'row2'), $rows[1]->id);
        $this->assertSame($this->idFromFixture(ElementRow::class, 'row3'), $rows[2]->id);

        // Row 1 → 2 columns
        $columns = $rows[0]->children;
        $this->assertCount(2, $columns);
        $this->assertSame('Left Column', $columns[0]->title);
        $this->assertSame('Right Column', $columns[1]->title);
        $this->assertSame($this->idFromFixture(ElementColumn::class, 'col1'), $columns[0]->id);
        $this->assertSame($this->idFromFixture(ElementColumn::class, 'col2'), $columns[1]->id);

        // Column 1 → 2 leaves
        $leaves = $columns[0]->children;
        $this->assertCount(2, $leaves);
        $this->assertSame('Text Block', $leaves[0]->title);
        $this->assertSame('Image Block', $leaves[1]->title);
        $this->assertSame($this->idFromFixture(BaseElement::class, 'leaf1'), $leaves[0]->id);
        $this->assertSame($this->idFromFixture(BaseElement::class, 'leaf2'), $leaves[1]->id);

        // Column 2 → 1 leaf
        $col2Leaves = $columns[1]->children;
        $this->assertCount(1, $col2Leaves);
        $this->assertSame('Video Block', $col2Leaves[0]->title);
        $this->assertSame($this->idFromFixture(BaseElement::class, 'leaf3'), $col2Leaves[0]->id);
    }

    // ---- Sort ordering ----

    public function testSiblingsOrderedBySort(): void
    {
        $tree = $this->buildTree();

        // Sections ordered by Sort
        $sectionTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree['ElementalArea']);
        $this->assertSame(['First Section', 'Second Section'], $sectionTitles);

        // Rows in section 1 ordered by Sort
        $rowTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree['ElementalArea'][0]->children);
        $this->assertSame(['First Row', 'Second Row', 'Empty Row'], $rowTitles);

        // Columns in row 1 ordered by Sort
        $columnTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree['ElementalArea'][0]->children[0]->children);
        $this->assertSame(['Left Column', 'Right Column'], $columnTitles);

        // Leaves in column 1 ordered by Sort
        $leafTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree['ElementalArea'][0]->children[0]->children[0]->children);
        $this->assertSame(['Text Block', 'Image Block'], $leafTitles);
    }

    // ---- Container fields ----

    public function testContainerNodeIncludesContainerFields(): void
    {
        $tree = $this->buildTree();
        $section = $tree['ElementalArea'][0];

        $this->assertNotNull($section->containerType);
        $this->assertSame('section', $section->containerType->value);

        $this->assertNotNull($section->allowedTypes);
        $this->assertArrayHasKey(ElementRow::class, $section->allowedTypes);

        $this->assertNotNull($section->children);
        $this->assertIsArray($section->children);
    }

    public function testLeafNodeExcludesContainerFields(): void
    {
        $tree = $this->buildTree();
        $leaf = $tree['ElementalArea'][0]->children[0]->children[0]->children[0];

        $this->assertNull($leaf->containerType);
        $this->assertNull($leaf->allowedTypes);
        $this->assertNull($leaf->children);
    }

    // ---- Base fields ----

    public function testBaseFieldsMatchElementData(): void
    {
        $tree = $this->buildTree();
        $leaf = $tree['ElementalArea'][0]->children[0]->children[0]->children[0];
        $fixtureId = $this->idFromFixture(BaseElement::class, 'leaf1');

        $this->assertSame($fixtureId, $leaf->id);
        $this->assertSame('Text Block', $leaf->title);
        $this->assertIsInt($leaf->version);
        $this->assertGreaterThan(0, $leaf->version);
        $this->assertFalse($leaf->isPublished);
        $this->assertFalse($leaf->isLiveVersion);
        $this->assertNull($leaf->obsoleteClassName);
        $this->assertIsBool($leaf->canDelete);
        $this->assertIsBool($leaf->canPublish);
        $this->assertIsBool($leaf->canUnpublish);
        $this->assertIsBool($leaf->canCreate);
        $this->assertIsArray($leaf->statusFlags);
    }

    public function testBlockSchemaStructure(): void
    {
        $tree = $this->buildTree();
        $leaf = $tree['ElementalArea'][0]->children[0]->children[0]->children[0];

        $schema = $leaf->blockSchema;

        $this->assertArrayHasKey('typeName', $schema);
        $this->assertIsString($schema['typeName']);
        $this->assertNotEmpty($schema['typeName']);

        $this->assertArrayHasKey('actions', $schema);
        $this->assertArrayHasKey('edit', $schema['actions']);

        $this->assertArrayHasKey('content', $schema);
        $this->assertIsString($schema['content']);
    }

    // ---- Empty containers ----

    public function testEmptyContainerHasEmptyChildrenArray(): void
    {
        $tree = $this->buildTree();

        // Section 2 (no rows)
        $section2 = $tree['ElementalArea'][1];
        $this->assertSame('Second Section', $section2->title);
        $this->assertSame([], $section2->children);

        // Row 3 (no columns)
        $row3 = $tree['ElementalArea'][0]->children[2];
        $this->assertSame('Empty Row', $row3->title);
        $this->assertSame([], $row3->children);

        // Column 3 (no leaves)
        $col3 = $tree['ElementalArea'][0]->children[1]->children[0];
        $this->assertSame('Full Width Column', $col3->title);
        $this->assertSame([], $col3->children);
    }

    // ---- Permissions ----

    public function testElementsWithCanViewFalseExcluded(): void
    {
        BaseElement::add_extension(DenyViewExtension::class);

        try {
            $tree = $this->buildTree();

            $this->assertSame([], $tree['ElementalArea']);
        } finally {
            BaseElement::remove_extension(DenyViewExtension::class);
        }
    }

    // ---- Extension hook ----

    public function testExtensionCanEnrichElementNode(): void
    {
        ElementTreeBuilder::add_extension(TestEnricherExtension::class);

        try {
            $tree = $this->buildTree();

            $section = $tree['ElementalArea'][0];
            $data = $section->jsonSerialize();

            $this->assertArrayHasKey('extensions', $data);
            $this->assertArrayHasKey('testEnricher', $data['extensions']);
            $this->assertSame('enriched', $data['extensions']['testEnricher']);

            // Verify enrichment propagates to nested nodes
            $leaf = $section->children[0]->children[0]->children[0];
            $leafData = $leaf->jsonSerialize();

            $this->assertArrayHasKey('extensions', $leafData);
            $this->assertSame('enriched', $leafData['extensions']['testEnricher']);
        } finally {
            ElementTreeBuilder::remove_extension(TestEnricherExtension::class);
        }
    }

    // ---- Empty states ----

    public function testPageWithNoElements(): void
    {
        $page = TestPage::create();
        $page->Title = 'Empty Page';
        $page->write();

        /** @var ElementTreeBuilder $builder */
        $builder = Injector::inst()->get(ElementTreeBuilder::class);
        $tree = $builder->buildForPage($page);

        $this->assertArrayHasKey('ElementalArea', $tree);
        $this->assertSame([], $tree['ElementalArea']);
    }
}

/**
 * Test extension that denies canView on all BaseElements.
 */
class DenyViewExtension extends Extension
{
    /**
     * @param mixed $member
     */
    public function canView($member): false
    {
        return false;
    }
}

/**
 * Test extension that enriches element nodes via the updateElementData hook.
 */
class TestEnricherExtension extends Extension
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function updateElementData(BaseElement $element, array &$extensions): void
    {
        $extensions['testEnricher'] = 'enriched';
    }
}
