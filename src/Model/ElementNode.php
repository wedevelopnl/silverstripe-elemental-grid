<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Model;

use WeDevelop\ElementalGrid\Contract\ContainerType;

/**
 * Readonly DTO representing a single element in the tree.
 *
 * Leaf nodes omit container-only fields (containerType, allowedTypes, children)
 * from the serialized output; container nodes include all three.
 *
 * @phpstan-type SerializedNode array{
 *     id: int,
 *     title: string,
 *     blockSchema: array{typeName: string, actions: array{edit: string}, content: string},
 *     obsoleteClassName: string|null,
 *     version: int,
 *     isPublished: bool,
 *     isLiveVersion: bool,
 *     canDelete: bool,
 *     canPublish: bool,
 *     canUnpublish: bool,
 *     canCreate: bool,
 *     statusFlags: array<string, mixed>,
 *     containerType?: string,
 *     allowedTypes?: array<class-string, string>|null,
 *     children?: list<mixed>|null,
 *     extensions?: array<string, mixed>,
 * }
 */
final readonly class ElementNode implements \JsonSerializable
{
    /**
     * @param array{typeName: string, actions: array{edit: string}, content: string} $blockSchema
     * @param array<string, mixed> $statusFlags
     * @param array<class-string, string>|null $allowedTypes
     * @param list<self>|null $children
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public int $id,
        public string $title,
        public array $blockSchema,
        public ?string $obsoleteClassName,
        public int $version,
        public bool $isPublished,
        public bool $isLiveVersion,
        public bool $canDelete,
        public bool $canPublish,
        public bool $canUnpublish,
        public bool $canCreate,
        public array $statusFlags,
        public ?ContainerType $containerType = null,
        public ?array $allowedTypes = null,
        public ?array $children = null,
        public array $extensions = [],
    ) {}

    /** @return SerializedNode */
    #[\Override]
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'blockSchema' => $this->blockSchema,
            'obsoleteClassName' => $this->obsoleteClassName,
            'version' => $this->version,
            'isPublished' => $this->isPublished,
            'isLiveVersion' => $this->isLiveVersion,
            'canDelete' => $this->canDelete,
            'canPublish' => $this->canPublish,
            'canUnpublish' => $this->canUnpublish,
            'canCreate' => $this->canCreate,
            'statusFlags' => $this->statusFlags,
        ];

        if ($this->containerType !== null) {
            $data['containerType'] = $this->containerType->value;
            $data['allowedTypes'] = $this->allowedTypes;
            $data['children'] = $this->children;
        }

        if ($this->extensions !== []) {
            $data['extensions'] = $this->extensions;
        }

        return $data;
    }
}
