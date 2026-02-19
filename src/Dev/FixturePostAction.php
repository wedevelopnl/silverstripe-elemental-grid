<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Dev;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Declarative post-action applied after YAML fixture loading.
 *
 * Encapsulates both the definition and execution of versioned state
 * manipulation (publish, unpublish, modify) on fixture records.
 * Used by {@see FixtureLoader} after YAML writing.
 */
final readonly class FixturePostAction
{
    private const array VALID_ACTIONS = [
        'publish_recursive',
        'unpublish',
        'modify',
    ];

    /**
     * @param 'publish_recursive'|'unpublish'|'modify' $action
     * @param class-string $class
     * @param array<string, string|int|float|bool> $fields Only used for 'modify' action
     */
    public function __construct(
        public string $action,
        public string $class,
        public string $identifier,
        public array $fields = [],
    ) {
    }

    /**
     * Execute this action against the given record.
     */
    public function apply(DataObject $record): void
    {
        /** @var DataObject&Versioned $record */
        match ($this->action) {
            'publish_recursive' => $record->publishRecursive(),
            'unpublish' => $record->doUnpublish(),
            'modify' => $this->applyModify($record),
        };
    }

    /**
     * @param array{action?: string, class?: class-string, identifier?: string, fields?: array<string, string|int|float|bool>} $config
     */
    public static function fromConfig(array $config): self
    {
        if (
            !is_string($config['action'] ?? null)
            || !is_string($config['class'] ?? null)
            || !is_string($config['identifier'] ?? null)
        ) {
            throw new \InvalidArgumentException(
                'Post-action config requires "action", "class", and "identifier" keys',
            );
        }

        $action = $config['action'];
        if (!in_array($action, self::VALID_ACTIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown post-action "%s". Valid actions: %s',
                    $action,
                    implode(', ', self::VALID_ACTIONS),
                ),
            );
        }

        /** @var array<string, string|int|float|bool> $fields */
        $fields = $config['fields'] ?? [];

        return new self(
            action: $action,
            class: $config['class'],
            identifier: $config['identifier'],
            fields: $fields,
        );
    }

    private function applyModify(DataObject $record): void
    {
        foreach ($this->fields as $field => $value) {
            $record->setField($field, $value);
        }

        $record->write();
    }
}
