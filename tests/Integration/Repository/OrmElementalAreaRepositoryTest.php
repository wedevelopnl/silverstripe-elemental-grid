<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Repository;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Repository\OrmElementalAreaRepository;
use WeDevelop\Grid\Tests\Integration\Fixture\TestPage;

#[CoversClass(OrmElementalAreaRepository::class)]
final class OrmElementalAreaRepositoryTest extends SapphireTest
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

    private OrmElementalAreaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
        $this->repository = new OrmElementalAreaRepository();
    }

    public function testFindByIdReturnsAreaWhenExists(): void
    {
        $expectedId = $this->idFromFixture(ElementalArea::class, 'page_area');

        $area = $this->repository->findById($expectedId);

        $this->assertNotNull($area);
        $this->assertSame($expectedId, (int) $area->ID);
    }

    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $area = $this->repository->findById(999999);

        $this->assertNull($area);
    }
}
