<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Extensions\GridPageExtension;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\GridNode;
use WeDevelop\Grid\Service\GridTreeBuilder;
use WeDevelop\Grid\Tests\Integration\Fixture\TestPage;

#[CoversClass(GridTreeBuilder::class)]
final class GridTreeBuilderTest extends SapphireTest
{
    protected static $fixture_file = __DIR__ . '/../Fixture/ElementTreeTest.yml';

    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            GridPageExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
    }

    public function onBeforeLoadFixtures(): void
    {
        parent::onBeforeLoadFixtures();
        Config::modify()->set(Section::class, 'auto_scaffold', false);
        Config::modify()->set(Row::class, 'auto_scaffold', false);
    }

    /**
     * @return array<int, list<GridNode>>
     */
    private function buildTree(): array
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        /** @var GridTreeBuilder $builder */
        $builder = Injector::inst()->get(GridTreeBuilder::class);

        return $builder->buildForPage($page);
    }

    /**
     * Get the page ID used as the tree root key.
     */
    private function getPageId(): int
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        return (int) $page->ID;
    }

    // ---- Full tree structure ----

    public function testTreeShapeMatchesFixtureHierarchy(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        $this->assertArrayHasKey($pageId, $tree);

        // 2 sections at root
        $sections = $tree[$pageId];
        $this->assertCount(2, $sections);
        $this->assertSame('First Section', $sections[0]->title);
        $this->assertSame('Second Section', $sections[1]->title);
        $this->assertSame($this->idFromFixture(Section::class, 'section1'), $sections[0]->id);
        $this->assertSame($this->idFromFixture(Section::class, 'section2'), $sections[1]->id);

        // Section 1 → 3 rows
        $rows = $sections[0]->children;
        $this->assertCount(3, $rows);
        $this->assertSame('First Row', $rows[0]->title);
        $this->assertSame('Second Row', $rows[1]->title);
        $this->assertSame('Empty Row', $rows[2]->title);
        $this->assertSame($this->idFromFixture(Row::class, 'row1'), $rows[0]->id);
        $this->assertSame($this->idFromFixture(Row::class, 'row2'), $rows[1]->id);
        $this->assertSame($this->idFromFixture(Row::class, 'row3'), $rows[2]->id);

        // Row 1 → 2 columns
        $columns = $rows[0]->children;
        $this->assertCount(2, $columns);
        $this->assertSame('Left Column', $columns[0]->title);
        $this->assertSame('Right Column', $columns[1]->title);
        $this->assertSame($this->idFromFixture(Column::class, 'col1'), $columns[0]->id);
        $this->assertSame($this->idFromFixture(Column::class, 'col2'), $columns[1]->id);

        // Column 1 → 2 leaves
        $leaves = $columns[0]->children;
        $this->assertCount(2, $leaves);
        $this->assertSame('Text Block', $leaves[0]->title);
        $this->assertSame('Image Block', $leaves[1]->title);
        $this->assertSame($this->idFromFixture(GridElement::class, 'leaf1'), $leaves[0]->id);
        $this->assertSame($this->idFromFixture(GridElement::class, 'leaf2'), $leaves[1]->id);

        // Column 2 → 1 leaf
        $col2Leaves = $columns[1]->children;
        $this->assertCount(1, $col2Leaves);
        $this->assertSame('Video Block', $col2Leaves[0]->title);
        $this->assertSame($this->idFromFixture(GridElement::class, 'leaf3'), $col2Leaves[0]->id);
    }

    // ---- parentId threading ----

    public function testParentAreaIdMatchesContainingArea(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        // Root sections: parentId equals the page ID
        $sections = $tree[$pageId];
        foreach ($sections as $section) {
            $this->assertSame($pageId, $section->parentId, 'Root section parentId should equal page ID');
        }

        // Rows: parentId equals parent section's id
        $section1 = $sections[0];
        foreach ($section1->children as $row) {
            $this->assertSame(
                $section1->id,
                $row->parentId,
                'Row parentId should equal parent section id',
            );

            // Columns: parentId equals parent row's id
            foreach ($row->children ?? [] as $column) {
                $this->assertSame(
                    $row->id,
                    $column->parentId,
                    'Column parentId should equal parent row id',
                );

                // Leaves: parentId equals parent column's id
                foreach ($column->children ?? [] as $leaf) {
                    $this->assertSame(
                        $column->id,
                        $leaf->parentId,
                        'Leaf parentId should equal parent column id',
                    );
                }
            }
        }
    }

    // ---- Sort ordering ----

    public function testSiblingsOrderedBySort(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        // Sections ordered by Sort
        $sectionTitles = \array_map(static fn (GridNode $n): string => $n->title, $tree[$pageId]);
        $this->assertSame(['First Section', 'Second Section'], $sectionTitles);

        // Rows in section 1 ordered by Sort
        $rowTitles = \array_map(static fn (GridNode $n): string => $n->title, $tree[$pageId][0]->children);
        $this->assertSame(['First Row', 'Second Row', 'Empty Row'], $rowTitles);

        // Columns in row 1 ordered by Sort
        $columnTitles = \array_map(static fn (GridNode $n): string => $n->title, $tree[$pageId][0]->children[0]->children);
        $this->assertSame(['Left Column', 'Right Column'], $columnTitles);

        // Leaves in column 1 ordered by Sort
        $leafTitles = \array_map(static fn (GridNode $n): string => $n->title, $tree[$pageId][0]->children[0]->children[0]->children);
        $this->assertSame(['Text Block', 'Image Block'], $leafTitles);
    }

    // ---- Container fields ----

    public function testContainerNodeIncludesContainerFields(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();
        $section = $tree[$pageId][0];

        $this->assertNotNull($section->containerType);
        $this->assertSame('section', $section->containerType->value);

        $this->assertNotNull($section->allowedTypes);
        $this->assertArrayHasKey(Row::class, $section->allowedTypes);

        $this->assertNotNull($section->children);
        $this->assertIsArray($section->children);
    }

    public function testLeafNodeExcludesContainerFields(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();
        $leaf = $tree[$pageId][0]->children[0]->children[0]->children[0];

        $this->assertNull($leaf->containerType);
        $this->assertNull($leaf->allowedTypes);
        $this->assertNull($leaf->children);
    }

    // ---- Base fields ----

    public function testBaseFieldsMatchElementData(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();
        $leaf = $tree[$pageId][0]->children[0]->children[0]->children[0];
        $fixtureId = $this->idFromFixture(GridElement::class, 'leaf1');

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
        $pageId = $this->getPageId();
        $leaf = $tree[$pageId][0]->children[0]->children[0]->children[0];

        $schema = $leaf->blockSchema;

        $this->assertArrayHasKey('typeName', $schema);
        $this->assertIsString($schema['typeName']);
        $this->assertNotEmpty($schema['typeName']);

        $this->assertArrayHasKey('type', $schema);
        $this->assertIsString($schema['type']);

        $this->assertArrayHasKey('summary', $schema);
        $this->assertIsString($schema['summary']);

        $this->assertArrayHasKey('label', $schema);
        $this->assertIsString($schema['label']);
        $this->assertNotEmpty($schema['label']);
    }

    // ---- Empty containers ----

    public function testEmptyContainerHasEmptyChildrenArray(): void
    {
        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        // Section 2 (no rows)
        $section2 = $tree[$pageId][1];
        $this->assertSame('Second Section', $section2->title);
        $this->assertSame([], $section2->children);

        // Row 3 (no columns)
        $row3 = $tree[$pageId][0]->children[2];
        $this->assertSame('Empty Row', $row3->title);
        $this->assertSame([], $row3->children);

        // Column 3 (no leaves)
        $col3 = $tree[$pageId][0]->children[1]->children[0];
        $this->assertSame('Full Width Column', $col3->title);
        $this->assertSame([], $col3->children);
    }

    // ---- Permissions ----

    public function testElementsWithCanViewFalseExcluded(): void
    {
        GridElement::add_extension(DenyViewExtension::class);

        try {
            $tree = $this->buildTree();
            $pageId = $this->getPageId();

            $this->assertSame([], $tree[$pageId]);
        } finally {
            GridElement::remove_extension(DenyViewExtension::class);
        }
    }

    // ---- Extension hook ----

    public function testExtensionCanEnrichGridNode(): void
    {
        GridTreeBuilder::add_extension(TestEnricherExtension::class);

        try {
            $tree = $this->buildTree();
            $pageId = $this->getPageId();

            $section = $tree[$pageId][0];
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
            GridTreeBuilder::remove_extension(TestEnricherExtension::class);
        }
    }

    // ---- canView: continue vs break ----

    public function testNonViewableElementDoesNotBlockSiblings(): void
    {
        $leaf1Id = $this->idFromFixture(GridElement::class, 'leaf1');
        DenySpecificViewExtension::$denyId = $leaf1Id;
        GridElement::add_extension(DenySpecificViewExtension::class);

        try {
            $tree = $this->buildTree();
            $pageId = $this->getPageId();

            // leaf1 is hidden but leaf2 (sibling) should still appear
            $col1Children = $tree[$pageId][0]->children[0]->children[0]->children;
            $titles = array_map(static fn (GridNode $n): string => $n->title, $col1Children);

            $this->assertNotContains('Text Block', $titles, 'Denied element should be excluded');
            $this->assertContains('Image Block', $titles, 'Sibling element should still appear');
        } finally {
            GridElement::remove_extension(DenySpecificViewExtension::class);
            DenySpecificViewExtension::$denyId = 0;
        }
    }

    // ---- Empty title fallback ----

    public function testEmptyTitleReturnsFallbackForLeafElement(): void
    {
        $leaf = $this->objFromFixture(GridElement::class, 'leaf1');
        $leaf->Title = '';
        $leaf->write();

        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        $node = $tree[$pageId][0]->children[0]->children[0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForColumn(): void
    {
        $col = $this->objFromFixture(Column::class, 'col1');
        $col->Title = '';
        $col->write();

        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        $node = $tree[$pageId][0]->children[0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForRow(): void
    {
        $row = $this->objFromFixture(Row::class, 'row1');
        $row->Title = '';
        $row->write();

        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        $node = $tree[$pageId][0]->children[0];
        $this->assertSame('(untitled)', $node->title);
    }

    public function testEmptyTitleReturnsFallbackForSection(): void
    {
        $section = $this->objFromFixture(Section::class, 'section1');
        $section->Title = '';
        $section->write();

        $tree = $this->buildTree();
        $pageId = $this->getPageId();

        $node = $tree[$pageId][0];
        $this->assertSame('(untitled)', $node->title);
    }

    // ---- Empty states ----

    public function testPageWithNoElements(): void
    {
        $page = TestPage::create();
        $page->Title = 'Empty Page';
        $page->write();

        /** @var GridTreeBuilder $builder */
        $builder = Injector::inst()->get(GridTreeBuilder::class);
        $tree = $builder->buildForPage($page);

        // Page has no sections, so the tree contains the page key
        // with an empty element list.
        $pageId = (int) $page->ID;
        $this->assertArrayHasKey($pageId, $tree);
        $this->assertSame([], $tree[$pageId]);
    }
}

/**
 * Test extension that denies canView on all GridElements.
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
    public function updateElementData(GridElement $element, array &$extensions): void
    {
        $extensions['testEnricher'] = 'enriched';
    }
}
