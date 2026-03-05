<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Extensions\GridPageExtension;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Repository\OrmGridElementRepository;
use WeDevelop\Grid\Tests\Integration\Fixture\TestPage;

#[CoversClass(OrmGridElementRepository::class)]
final class OrmGridElementRepositoryTest extends SapphireTest
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

    private OrmGridElementRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
        $this->repository = new OrmGridElementRepository();
    }

    public function onBeforeLoadFixtures(): void
    {
        parent::onBeforeLoadFixtures();
        Config::modify()->set(Section::class, 'auto_scaffold', false);
        Config::modify()->set(Row::class, 'auto_scaffold', false);
    }

    // ---- findById ----

    public function testFindByIdReturnsElementWhenExists(): void
    {
        $expectedId = $this->idFromFixture(GridElement::class, 'leaf1');

        $element = $this->repository->findById($expectedId);

        $this->assertNotNull($element);
        $this->assertSame($expectedId, (int) $element->ID);
        $this->assertSame('Text Block', $element->Title);
    }

    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $element = $this->repository->findById(999999);

        $this->assertNull($element);
    }

    // ---- findByParentIds ----

    public function testFindByParentIdsReturnsSortedElements(): void
    {
        $col1Id = $this->idFromFixture(Column::class, 'col1');

        $elements = $this->repository->findByParentIds([$col1Id]);

        $this->assertCount(2, $elements);
        $this->assertSame('Text Block', $elements[0]->Title);
        $this->assertSame('Image Block', $elements[1]->Title);
    }

    public function testFindByParentIdsReturnsElementsFromMultipleParents(): void
    {
        $col1Id = $this->idFromFixture(Column::class, 'col1');
        $col2Id = $this->idFromFixture(Column::class, 'col2');

        $elements = $this->repository->findByParentIds([$col1Id, $col2Id]);

        $this->assertCount(3, $elements);

        $titles = array_map(static fn (GridElement $e): string => $e->Title, $elements);
        $this->assertContains('Text Block', $titles);
        $this->assertContains('Image Block', $titles);
        $this->assertContains('Video Block', $titles);
    }

    public function testFindByParentIdsSortsBySortFieldNotId(): void
    {
        $leaf1 = $this->objFromFixture(GridElement::class, 'leaf1');
        $leaf2 = $this->objFromFixture(GridElement::class, 'leaf2');

        // Swap sort values so lower-ID element has higher Sort
        $leaf1->Sort = 2;
        $leaf1->write();
        $leaf2->Sort = 1;
        $leaf2->write();

        $col1Id = $this->idFromFixture(Column::class, 'col1');

        $elements = $this->repository->findByParentIds([$col1Id]);

        $this->assertCount(2, $elements);
        // leaf2 (Sort=1) should come first despite having a higher ID than leaf1
        $this->assertSame('Image Block', $elements[0]->Title);
        $this->assertSame('Text Block', $elements[1]->Title);
    }

    public function testFindByParentIdsReturnsEmptyArrayForEmptyInput(): void
    {
        $elements = $this->repository->findByParentIds([]);

        $this->assertSame([], $elements);
    }

    public function testFindByParentIdsReturnsEmptyArrayForNonExistentParent(): void
    {
        $elements = $this->repository->findByParentIds([999999]);

        $this->assertSame([], $elements);
    }
}
