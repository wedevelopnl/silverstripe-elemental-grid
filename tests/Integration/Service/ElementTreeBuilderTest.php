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
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\MultiAreaTestPage;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

#[CoversClass(ElementTreeBuilder::class)]
final class ElementTreeBuilderTest extends SapphireTest
{
    protected static $fixture_file = __DIR__ . '/../Fixture/ElementTreeTest.yml';

    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
        MultiAreaTestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
        ],
        MultiAreaTestPage::class => [
            ElementalPageExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
    }

    /**
     * @return array<int, list<ElementNode>>
     */
    private function buildTree(): array
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        /** @var ElementTreeBuilder $builder */
        $builder = Injector::inst()->get(ElementTreeBuilder::class);

        return $builder->buildForPage($page);
    }

    /**
     * Get the area ID for the test page's ElementalArea relation.
     */
    private function getAreaId(): int
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        return (int) $page->ElementalAreaID;
    }

    // ---- Full tree structure ----

    public function testTreeShapeMatchesFixtureHierarchy(): void
    {
        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        $this->assertArrayHasKey($areaId, $tree);

        // 2 sections at root
        $sections = $tree[$areaId];
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
        $areaId = $this->getAreaId();

        // Sections ordered by Sort
        $sectionTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree[$areaId]);
        $this->assertSame(['First Section', 'Second Section'], $sectionTitles);

        // Rows in section 1 ordered by Sort
        $rowTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree[$areaId][0]->children);
        $this->assertSame(['First Row', 'Second Row', 'Empty Row'], $rowTitles);

        // Columns in row 1 ordered by Sort
        $columnTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree[$areaId][0]->children[0]->children);
        $this->assertSame(['Left Column', 'Right Column'], $columnTitles);

        // Leaves in column 1 ordered by Sort
        $leafTitles = \array_map(static fn (ElementNode $n): string => $n->title, $tree[$areaId][0]->children[0]->children[0]->children);
        $this->assertSame(['Text Block', 'Image Block'], $leafTitles);
    }

    // ---- Container fields ----

    public function testContainerNodeIncludesContainerFields(): void
    {
        $tree = $this->buildTree();
        $areaId = $this->getAreaId();
        $section = $tree[$areaId][0];

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
        $areaId = $this->getAreaId();
        $leaf = $tree[$areaId][0]->children[0]->children[0]->children[0];

        $this->assertNull($leaf->containerType);
        $this->assertNull($leaf->allowedTypes);
        $this->assertNull($leaf->children);
    }

    // ---- Base fields ----

    public function testBaseFieldsMatchElementData(): void
    {
        $tree = $this->buildTree();
        $areaId = $this->getAreaId();
        $leaf = $tree[$areaId][0]->children[0]->children[0]->children[0];
        $fixtureId = $this->idFromFixture(BaseElement::class, 'leaf1');

        $this->assertSame($fixtureId, $leaf->id);
        $this->assertSame('Text Block', $leaf->title);
        $this->assertIsInt($leaf->version);
        $this->assertGreaterThan(0, $leaf->version);
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
        $areaId = $this->getAreaId();
        $leaf = $tree[$areaId][0]->children[0]->children[0]->children[0];

        $schema = $leaf->blockSchema;

        $this->assertArrayHasKey('typeName', $schema);
        $this->assertIsString($schema['typeName']);
        $this->assertNotEmpty($schema['typeName']);

        $this->assertArrayHasKey('actions', $schema);
        $this->assertArrayHasKey('edit', $schema['actions']);

        $this->assertArrayHasKey('content', $schema);
        $this->assertIsString($schema['content']);

        $this->assertArrayHasKey('label', $schema);
        $this->assertIsString($schema['label']);
        $this->assertNotEmpty($schema['label']);
    }

    // ---- Empty containers ----

    public function testEmptyContainerHasEmptyChildrenArray(): void
    {
        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        // Section 2 (no rows)
        $section2 = $tree[$areaId][1];
        $this->assertSame('Second Section', $section2->title);
        $this->assertSame([], $section2->children);

        // Row 3 (no columns)
        $row3 = $tree[$areaId][0]->children[2];
        $this->assertSame('Empty Row', $row3->title);
        $this->assertSame([], $row3->children);

        // Column 3 (no leaves)
        $col3 = $tree[$areaId][0]->children[1]->children[0];
        $this->assertSame('Full Width Column', $col3->title);
        $this->assertSame([], $col3->children);
    }

    // ---- Permissions ----

    public function testElementsWithCanViewFalseExcluded(): void
    {
        BaseElement::add_extension(DenyViewExtension::class);

        try {
            $tree = $this->buildTree();
            $areaId = $this->getAreaId();

            $this->assertSame([], $tree[$areaId]);
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
            $areaId = $this->getAreaId();

            $section = $tree[$areaId][0];
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

    // ---- canView: continue vs break ----

    public function testNonViewableElementDoesNotBlockSiblings(): void
    {
        $leaf1Id = $this->idFromFixture(BaseElement::class, 'leaf1');
        DenySpecificViewExtension::$denyId = $leaf1Id;
        BaseElement::add_extension(DenySpecificViewExtension::class);

        try {
            $tree = $this->buildTree();
            $areaId = $this->getAreaId();

            // leaf1 is hidden but leaf2 (sibling) should still appear
            $col1Children = $tree[$areaId][0]->children[0]->children[0]->children;
            $titles = array_map(static fn (ElementNode $n): string => $n->title, $col1Children);

            $this->assertNotContains('Text Block', $titles, 'Denied element should be excluded');
            $this->assertContains('Image Block', $titles, 'Sibling element should still appear');
        } finally {
            BaseElement::remove_extension(DenySpecificViewExtension::class);
            DenySpecificViewExtension::$denyId = 0;
        }
    }

    // ---- Multi-area pages ----

    public function testBuildForPageReturnsMultipleAreaKeys(): void
    {
        $page = MultiAreaTestPage::create();
        $page->Title = 'Multi Area Test';
        $page->write();

        // Create sections in both areas
        $primarySection = ElementSection::create();
        $primarySection->Title = 'Primary Section';
        $primarySection->ParentID = $page->ElementalAreaID;
        $primarySection->write();

        $secondarySection = ElementSection::create();
        $secondarySection->Title = 'Secondary Section';
        $secondarySection->ParentID = $page->SecondaryAreaID;
        $secondarySection->write();

        /** @var ElementTreeBuilder $builder */
        $builder = Injector::inst()->get(ElementTreeBuilder::class);
        $tree = $builder->buildForPage($page);

        $this->assertCount(2, $tree, 'Tree should have entries for both elemental areas');
        $this->assertArrayHasKey((int) $page->ElementalAreaID, $tree);
        $this->assertArrayHasKey((int) $page->SecondaryAreaID, $tree);
    }

    // ---- Empty title fallback ----

    public function testEmptyTitleReturnsFallbackForLeafElement(): void
    {
        $leaf = $this->objFromFixture(BaseElement::class, 'leaf1');
        $leaf->Title = '';
        $leaf->write();

        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        $node = $tree[$areaId][0]->children[0]->children[0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForColumn(): void
    {
        $col = $this->objFromFixture(ElementColumn::class, 'col1');
        $col->Title = '';
        $col->write();

        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        $node = $tree[$areaId][0]->children[0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForRow(): void
    {
        $row = $this->objFromFixture(ElementRow::class, 'row1');
        $row->Title = '';
        $row->write();

        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        $node = $tree[$areaId][0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForSection(): void
    {
        $section = $this->objFromFixture(ElementSection::class, 'section1');
        $section->Title = '';
        $section->write();

        $tree = $this->buildTree();
        $areaId = $this->getAreaId();

        $node = $tree[$areaId][0];
        $this->assertSame('(untitled)', $node->title);
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

        // ElementalPageExtension auto-creates an area on write, so the tree
        // contains the area key with an empty element list.
        $areaId = (int) $page->ElementalAreaID;
        $this->assertArrayHasKey($areaId, $tree);
        $this->assertSame([], $tree[$areaId]);
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
 * Test extension that denies canView for a single element by ID.
 *
 * Unlike {@see DenyViewExtension} which denies ALL elements, this allows
 * testing that continue (not break) is used in the tree builder loop.
 */
class DenySpecificViewExtension extends Extension
{
    public static int $denyId = 0;

    /**
     * @param mixed $member
     */
    public function canView($member): ?bool
    {
        return $this->owner->ID === self::$denyId ? false : null;
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
