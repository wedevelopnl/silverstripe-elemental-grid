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
 *     statusFlags: \stdClass&object{addedtodraft?: string, modified?: string, removedfromdraft?: string},
 *     containerType?: string,
 *     allowedTypes?: array<class-string, string>|null,
 *     children?: list<mixed>|null,
 *     gridSettings?: array<string, array{width: int, offset: int, visible: bool}>,
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
     * @param array<string, array{width: int, offset: int, visible: bool}>|null $gridSettings
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
        public ?array $gridSettings = null,
        public array $extensions = [],
    ) {
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
            'statusFlags' => $statusFlags,
        ];

        if ($this->containerType !== null) {
            $data['containerType'] = $this->containerType->value;
            $data['allowedTypes'] = $this->allowedTypes;
            $data['children'] = $this->children;
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
