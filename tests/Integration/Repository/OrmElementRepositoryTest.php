<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Repository;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Repository\OrmElementRepository;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

#[CoversClass(OrmElementRepository::class)]
final class OrmElementRepositoryTest extends SapphireTest
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

    private OrmElementRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
        $this->repository = new OrmElementRepository();
    }

    // ---- findById ----

    public function testFindByIdReturnsElementWhenExists(): void
    {
        $expectedId = $this->idFromFixture(BaseElement::class, 'leaf1');

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

    // ---- findByAreaIds ----

    public function testFindByAreaIdsReturnsSortedElements(): void
    {
        $col1AreaId = $this->idFromFixture(\DNADesign\Elemental\Models\ElementalArea::class, 'col1_area');

        $elements = $this->repository->findByAreaIds([$col1AreaId]);

        $this->assertCount(2, $elements);
        $this->assertSame('Text Block', $elements[0]->Title);
        $this->assertSame('Image Block', $elements[1]->Title);
    }

    public function testFindByAreaIdsReturnsElementsFromMultipleAreas(): void
    {
        $col1AreaId = $this->idFromFixture(\DNADesign\Elemental\Models\ElementalArea::class, 'col1_area');
        $col2AreaId = $this->idFromFixture(\DNADesign\Elemental\Models\ElementalArea::class, 'col2_area');

        $elements = $this->repository->findByAreaIds([$col1AreaId, $col2AreaId]);

        $this->assertCount(3, $elements);

        $titles = array_map(static fn (BaseElement $e): string => $e->Title, $elements);
        $this->assertContains('Text Block', $titles);
        $this->assertContains('Image Block', $titles);
        $this->assertContains('Video Block', $titles);
    }

    public function testFindByAreaIdsReturnsEmptyArrayForEmptyInput(): void
    {
        $elements = $this->repository->findByAreaIds([]);

        $this->assertSame([], $elements);
    }

    public function testFindByAreaIdsReturnsEmptyArrayForNonExistentArea(): void
    {
        $elements = $this->repository->findByAreaIds([999999]);

        $this->assertSame([], $elements);
    }
}
