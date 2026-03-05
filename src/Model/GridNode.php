<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use WeDevelop\Grid\Contract\ContainerType;

/**
 * Readonly DTO representing a single element in the tree.
 *
 * Leaf nodes omit container-only fields (containerType, allowedTypes, children)
 * from the serialized output; container nodes include all three.
 *
 * @phpstan-type SerializedNode array{
 *     id: int,
 *     parentId: positive-int,
 *     title: string,
 *     blockSchema: array{typeName: string, actions: array{edit: string}, content: string, label: string},
 *     obsoleteClassName: string|null,
 *     version: int,
 *     canDelete: bool,
 *     canPublish: bool,
 *     canUnpublish: bool,
 *     canCreate: bool,
 *     statusFlags: \stdClass&object{addedtodraft?: array{text: string, title: string}, modified?: array{text: string, title: string}, removedfromdraft?: array{text: string, title: string}},
 *     containerType?: string,
 *     allowedTypes?: array<class-string, string>|null,
 *     children?: list<mixed>|null,
 *     gridSettings?: array<string, array{width: int, offset: int, visible: bool}>,
 *     extensions?: array<string, mixed>,
 * }
 */
final readonly class GridNode implements \JsonSerializable
{
    /**
     * @param positive-int $parentId
     * @param array{typeName: string, actions: array{edit: string}, content: string, label: string} $blockSchema
     * @param array<string, array{text: string, title: string}> $statusFlags
     * @param array<class-string, string>|null $allowedTypes
     * @param list<self>|null $children
     * @param array<string, array{width: int, offset: int, visible: bool}>|null $gridSettings
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public int $id,
        public int $parentId,
        public string $title,
        public array $blockSchema,
        public ?string $obsoleteClassName,
        public int $version,
        public bool $canDelete,
        public bool $canPublish,
        public bool $canUnpublish,
        public bool $canCreate,
        public array $statusFlags,
        public ?ContainerType $containerType = null,
        public ?array $allowedTypes = null,
        public ?array $children = null,
        public ?array $gridSettings = null,
        public array $extensions = [],
    ) {
        if ($parentId <= 0) { // @phpstan-ignore smallerOrEqual.alwaysFalse (runtime guard: native type is int)
            throw new \InvalidArgumentException(
                'parentId must be a positive integer',
            );
        }

        if ($gridSettings !== null && $containerType !== ContainerType::Column) {
            throw new \InvalidArgumentException(
                'gridSettings may only be provided for Column container type',
            );
        }
    }

    /** @return SerializedNode */
    #[\Override]
    public function jsonSerialize(): array
    {
        /** @var SerializedNode['statusFlags'] $statusFlags */
        $statusFlags = (object) $this->statusFlags;

        $data = [
            'id' => $this->id,
            'parentId' => $this->parentId,
            'title' => $this->title,
            'blockSchema' => $this->blockSchema,
            'obsoleteClassName' => $this->obsoleteClassName,
            'version' => $this->version,
            'canDelete' => $this->canDelete,
            'canPublish' => $this->canPublish,
            'canUnpublish' => $this->canUnpublish,
            'canCreate' => $this->canCreate,
            'statusFlags' => $statusFlags,
        ];

        if ($this->containerType !== null) {
            $data['containerType'] = $this->containerType->value;
            $data['allowedTypes'] = $this->allowedTypes;
            // Widen list<self> to list<mixed> for the serialized return type
            /** @var list<mixed>|null $children */
            $children = $this->children;
            $data['children'] = $children;
        }

        if ($this->containerType === ContainerType::Column && $this->gridSettings !== null) {
            $data['gridSettings'] = $this->gridSettings;
        }

        if ($this->extensions !== []) {
            $data['extensions'] = $this->extensions;
        }

        return $data;
    }
}
