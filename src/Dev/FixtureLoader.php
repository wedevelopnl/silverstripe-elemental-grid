<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Dev;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\YamlFixture;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;

/**
 * Loads and resets YAML fixtures at runtime for E2E tests.
 *
 * Playwright calls {@see FixtureController} which delegates to this class.
 * Fixtures are registered via config and resolved through ModuleResourceLoader
 * so vendor:path syntax works.
 *
 * Fixtures can be a simple path string or an array with `path` and optional
 * `post_actions` for manipulating versioned state after YAML loading.
 */
class FixtureLoader
{
    use Injectable;
    use Configurable;

    private const string URL_SEGMENT_PREFIX = 'e2e-';

    /**
     * Map of fixture names to YAML file paths or config arrays.
     *
     * String value: 'vendor/package:path/to/file.yml'
     * Array value: { path: 'vendor/package:path.yml', post_actions: [...] }
     *
     * @var array<string, string|array{path: string, post_actions?: list<array{action: string, class: string, identifier: string, fields?: array<string, mixed>}>}>
     */
    private static array $fixtures = [];

    /**
     * Load a named fixture into the database.
     *
     * Resets existing E2E data first to guarantee idempotency,
     * then writes the YAML fixture, applies any post-actions,
     * and returns a result with the page ID and full fixture map.
     */
    public function load(string $name): FixtureResult
    {
        $path = $this->resolveFixturePath($name);
        $this->reset();

        $factory = new FixtureFactory();
        $fixture = YamlFixture::create($path);

        // Suppress auto-scaffolding so YAML can define the exact tree structure
        // without containers creating duplicate children on write.
        Section::$autoScaffold = false;
        Row::$autoScaffold = false;

        try {
            Versioned::withVersionedMode(static function () use ($fixture, $factory): void {
                Versioned::set_stage(Versioned::DRAFT);
                $fixture->writeInto($factory);
            });
        } finally {
            Section::$autoScaffold = true;
            Row::$autoScaffold = true;
        }

        $postActions = $this->resolvePostActions($name);
        if ($postActions !== []) {
            $this->applyPostActions($postActions, $factory);
        }

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
     * Uses doArchive() which cascades through cascade_deletes,
     * removing from both Draft and Live.
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
        /** @var array<string, string|array<string, mixed>> $fixtures */
        $fixtures = static::config()->get('fixtures');

        return array_keys($fixtures);
    }

    /**
     * Resolve a fixture name to an absolute file path.
     *
     * Accepts both string config ('path.yml') and array config
     * ({ path: 'path.yml', post_actions: [...] }).
     *
     * @throws \InvalidArgumentException If the fixture name is not registered or the file doesn't exist
     */
    private function resolveFixturePath(string $name): string
    {
        /** @var array<string, string|array<string, mixed>> $fixtures */
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

        $config = $fixtures[$name];
        $resourcePath = is_array($config) ? ($config['path'] ?? null) : $config;

        if (!is_string($resourcePath) || $resourcePath === '') {
            throw new \InvalidArgumentException(
                sprintf('Fixture "%s" has no path configured', $name),
            );
        }

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

    /**
     * Extract post-actions from a fixture's array config.
     *
     * @return list<FixturePostAction>
     */
    private function resolvePostActions(string $name): array
    {
        /** @var array<string, string|array<string, mixed>> $fixtures */
        $fixtures = static::config()->get('fixtures');

        $config = $fixtures[$name] ?? null;
        if (!is_array($config) || !isset($config['post_actions'])) {
            return [];
        }

        /** @var list<array{action?: string, class?: class-string, identifier?: string, fields?: array<string, string|int|float|bool>}> $rawActions */
        $rawActions = $config['post_actions'];

        return array_map(
            static fn (array $actionConfig): FixturePostAction => FixturePostAction::fromConfig($actionConfig),
            $rawActions,
        );
    }

    /**
     * Resolve fixture identifiers to records and delegate execution
     * to each {@see FixturePostAction}.
     *
     * @param list<FixturePostAction> $actions
     */
    private function applyPostActions(array $actions, FixtureFactory $factory): void
    {
        Versioned::withVersionedMode(static function () use ($actions, $factory): void {
            Versioned::set_stage(Versioned::DRAFT);

            foreach ($actions as $action) {
                $id = $factory->getId($action->class, $action->identifier);
                if ($id === false || $id === 0) {
                    throw new \RuntimeException(
                        sprintf(
                            'Post-action references unknown fixture: %s.%s',
                            $action->class,
                            $action->identifier,
                        ),
                    );
                }

                $record = DataObject::get($action->class)->byID($id);
                if ($record === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Record not found for post-action: %s #%d (%s)',
                            $action->class,
                            $id,
                            $action->identifier,
                        ),
                    );
                }

                $action->apply($record);
            }
        });
    }
}
