<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Dev;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\YamlFixture;
use SilverStripe\Versioned\Versioned;

/**
 * Loads and resets YAML fixtures at runtime for E2E tests.
 *
 * Playwright calls {@see FixtureController} which delegates to this class.
 * Fixtures are registered via config and resolved through ModuleResourceLoader
 * so vendor:path syntax works.
 */
class FixtureLoader
{
    use Injectable;
    use Configurable;

    private const string URL_SEGMENT_PREFIX = 'e2e-';

    /**
     * Map of fixture names to YAML file paths.
     * Paths use module resource syntax: 'vendor/package:path/to/file.yml'
     *
     * @var array<string, string>
     */
    private static array $fixtures = [];

    /**
     * Load a named fixture into the database.
     *
     * Resets existing E2E data first to guarantee idempotency,
     * then writes the YAML fixture and returns a result with
     * the page ID and full fixture map.
     */
    public function load(string $name): FixtureResult
    {
        $path = $this->resolveFixturePath($name);
        $this->reset();

        $factory = new FixtureFactory();
        $fixture = YamlFixture::create($path);

        Versioned::withVersionedMode(static function () use ($fixture, $factory): void {
            Versioned::set_stage(Versioned::DRAFT);
            $fixture->writeInto($factory);
        });

        $pageClass = Page::class;
        /** @var array<string, int>|false $pageIds */
        $pageIds = $factory->getIds($pageClass);
        if ($pageIds === false || $pageIds === []) {
            throw new \RuntimeException(
                sprintf('Fixture "%s" did not create any %s records', $name, $pageClass),
            );
        }

        $pageIdentifier = array_key_first($pageIds);
        $pageId = $pageIds[$pageIdentifier];

        /** @var SiteTree|null $page */
        $page = Versioned::withVersionedMode(static function () use ($pageId): ?SiteTree {
            Versioned::set_stage(Versioned::DRAFT);

            return SiteTree::get()->byID($pageId);
        });

        if ($page === null) {
            throw new \RuntimeException(
                sprintf('Page ID %d from fixture "%s" not found after write', $pageId, $name),
            );
        }

        /** @var array<string, array<string, int>> $fixtureMap */
        $fixtureMap = $factory->getFixtures();

        return new FixtureResult(
            fixtureName: $name,
            pageId: $pageId,
            pageUrl: $page->Link(),
            fixtureMap: $fixtureMap,
        );
    }

    /**
     * Remove all E2E pages (identified by URLSegment prefix).
     *
     * Uses doArchive() which cascades through ElementalArea → Elements
     * via existing cascade_deletes, removing from both Draft and Live.
     */
    public function reset(): void
    {
        Versioned::withVersionedMode(static function (): void {
            Versioned::set_stage(Versioned::DRAFT);

            $pages = SiteTree::get()->filter(
                'URLSegment:StartsWith',
                self::URL_SEGMENT_PREFIX,
            );

            foreach ($pages as $page) {
                $page->doArchive();
            }
        });
    }

    /**
     * @return list<string>
     */
    public function getAvailableFixtures(): array
    {
        /** @var array<string, string> $fixtures */
        $fixtures = static::config()->get('fixtures');

        return array_keys($fixtures);
    }

    /**
     * Resolve a fixture name to an absolute file path.
     *
     * @throws \InvalidArgumentException If the fixture name is not registered or the file doesn't exist
     */
    private function resolveFixturePath(string $name): string
    {
        /** @var array<string, string> $fixtures */
        $fixtures = static::config()->get('fixtures');

        if (!isset($fixtures[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown fixture "%s". Available: %s',
                    $name,
                    implode(', ', array_keys($fixtures)),
                ),
            );
        }

        $resourcePath = $fixtures[$name];

        // Resolve module resource syntax (vendor/package:path)
        $resolved = ModuleResourceLoader::singleton()->resolvePath($resourcePath);
        if ($resolved === null) {
            throw new \InvalidArgumentException(
                sprintf('Could not resolve fixture path "%s" for fixture "%s"', $resourcePath, $name),
            );
        }

        // resolvePath returns a BASE_PATH-relative path
        $absolutePath = Director::baseFolder() . '/' . $resolved;

        if (!file_exists($absolutePath)) {
            throw new \InvalidArgumentException(
                sprintf('Fixture file not found: %s (resolved from "%s")', $absolutePath, $resourcePath),
            );
        }

        return $absolutePath;
    }
}
