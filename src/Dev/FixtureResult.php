<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Dev;

use JsonSerializable;

/**
 * Immutable result of loading a fixture via {@see FixtureLoader}.
 *
 * Returned as JSON from {@see FixtureController} so Playwright
 * can extract page IDs for CMS navigation.
 */
final readonly class FixtureResult implements JsonSerializable
{
    /**
     * @param array<string, array<string, int>> $fixtureMap Class → identifier → DB ID
     */
    public function __construct(
        public string $fixtureName,
        public int $pageId,
        public string $pageUrl,
        public array $fixtureMap,
    ) {
    }

    /**
     * @return array{pageId: int, pageUrl: string, fixtureMap: array<string, array<string, int>>}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'pageId' => $this->pageId,
            'pageUrl' => $this->pageUrl,
            'fixtureMap' => $this->fixtureMap,
        ];
    }
}
