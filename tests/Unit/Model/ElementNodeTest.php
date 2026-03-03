<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Model\ElementNode;

#[CoversClass(ElementNode::class)]
final class ElementNodeTest extends TestCase
{
    private function createLeafNode(int $id = 1, string $title = 'Leaf'): ElementNode
    {
        return new ElementNode(
            id: $id,
            title: $title,
            blockSchema: ['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Base Element'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
        );
    }

    private function createContainerNode(
        ContainerType $containerType = ContainerType::Section,
        ?array $children = [],
    ): ElementNode {
        return new ElementNode(
            id: 10,
            title: 'Container',
            blockSchema: ['typeName' => 'ElementSection', 'actions' => ['edit' => '/edit/10'], 'content' => '', 'label' => 'Section'],
            obsoleteClassName: null,
            version: 2,
            canDelete: true,
            canPublish: true,
            canUnpublish: true,
            canCreate: true,
            statusFlags: ['modified' => ['text' => 'Modified', 'title' => 'Item has unpublished changes']],
            containerType: $containerType,
            allowedTypes: ['App\\Elements\\Row' => 'Row'],
            children: $children,
        );
    }

    public function testLeafNodeSerializesWithoutContainerFields(): void
    {
        $node = $this->createLeafNode();
        $data = $node->jsonSerialize();

        $this->assertArrayNotHasKey('containerType', $data);
        $this->assertArrayNotHasKey('allowedTypes', $data);
        $this->assertArrayNotHasKey('children', $data);
    }

    public function testLeafNodeIncludesAllBaseFields(): void
    {
        $node = $this->createLeafNode(id: 42, title: 'My Block');
        $data = $node->jsonSerialize();

        $this->assertSame(42, $data['id']);
        $this->assertSame('My Block', $data['title']);
        $this->assertSame(['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Base Element'], $data['blockSchema']);
        $this->assertNull($data['obsoleteClassName']);
        $this->assertSame(1, $data['version']);
        $this->assertTrue($data['canDelete']);
        $this->assertTrue($data['canPublish']);
        $this->assertFalse($data['canUnpublish']);
        $this->assertTrue($data['canCreate']);
        $this->assertEquals(new \stdClass(), $data['statusFlags']);
    }

    public function testContainerNodeSerializesWithContainerFields(): void
    {
        $node = $this->createContainerNode();
        $data = $node->jsonSerialize();

        $this->assertArrayHasKey('containerType', $data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertArrayHasKey('children', $data);

        $this->assertSame('section', $data['containerType']);
        $this->assertSame(['App\\Elements\\Row' => 'Row'], $data['allowedTypes']);
        $this->assertSame([], $data['children']);
    }

    public function testContainerTypeSerializesAsString(): void
    {
        foreach (ContainerType::cases() as $case) {
            $node = $this->createContainerNode(containerType: $case);
            $data = $node->jsonSerialize();

            $this->assertSame($case->value, $data['containerType']);
        }
    }

    public function testChildrenSerializeRecursively(): void
    {
        $leaf1 = $this->createLeafNode(id: 100, title: 'Child A');
        $leaf2 = $this->createLeafNode(id: 101, title: 'Child B');
        $container = $this->createContainerNode(children: [$leaf1, $leaf2]);

        $json = json_encode($container, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        $this->assertCount(2, $decoded['children']);
        $this->assertSame(100, $decoded['children'][0]['id']);
        $this->assertSame('Child A', $decoded['children'][0]['title']);
        $this->assertSame(101, $decoded['children'][1]['id']);
        $this->assertSame('Child B', $decoded['children'][1]['title']);

        // Children are leaves — no container fields
        $this->assertArrayNotHasKey('containerType', $decoded['children'][0]);
        $this->assertArrayNotHasKey('children', $decoded['children'][0]);
    }

    public function testExtensionsIncludedInSerialization(): void
    {
        $node = new ElementNode(
            id: 1,
            title: 'Leaf',
            blockSchema: ['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Base Element'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            extensions: ['gridSettings' => ['span' => 6, 'offset' => 0]],
        );

        $data = $node->jsonSerialize();

        $this->assertArrayHasKey('extensions', $data);
        $this->assertSame(['span' => 6, 'offset' => 0], $data['extensions']['gridSettings']);
    }

    public function testEmptyExtensionsOmittedFromSerialization(): void
    {
        $node = $this->createLeafNode();
        $data = $node->jsonSerialize();

        $this->assertArrayNotHasKey('extensions', $data);
    }

    public function testExtensionsOnLeafNode(): void
    {
        $node = new ElementNode(
            id: 1,
            title: 'Leaf',
            blockSchema: ['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Base Element'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            extensions: ['custom' => 'value'],
        );

        $data = $node->jsonSerialize();

        $this->assertArrayHasKey('extensions', $data);
        $this->assertSame(['custom' => 'value'], $data['extensions']);
        $this->assertArrayNotHasKey('containerType', $data);
        $this->assertArrayNotHasKey('allowedTypes', $data);
        $this->assertArrayNotHasKey('children', $data);
    }

    public function testExtensionsOnContainerNode(): void
    {
        $node = new ElementNode(
            id: 10,
            title: 'Container',
            blockSchema: ['typeName' => 'ElementSection', 'actions' => ['edit' => '/edit/10'], 'content' => '', 'label' => 'Section'],
            obsoleteClassName: null,
            version: 2,
            canDelete: true,
            canPublish: true,
            canUnpublish: true,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Section,
            allowedTypes: ['App\\Elements\\Row' => 'Row'],
            children: [],
            extensions: ['layout' => 'fluid'],
        );

        $data = $node->jsonSerialize();

        $this->assertArrayHasKey('containerType', $data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertArrayHasKey('children', $data);
        $this->assertArrayHasKey('extensions', $data);
        $this->assertSame(['layout' => 'fluid'], $data['extensions']);
    }

    public function testNestedContainersSerializeRecursively(): void
    {
        $leaf = $this->createLeafNode(id: 200, title: 'Deep Leaf');
        $innerContainer = $this->createContainerNode(
            containerType: ContainerType::Column,
            children: [$leaf],
        );
        $outerContainer = new ElementNode(
            id: 50,
            title: 'Outer',
            blockSchema: ['typeName' => 'ElementRow', 'actions' => ['edit' => '/edit/50'], 'content' => '', 'label' => 'Row'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Row,
            allowedTypes: ['App\\Elements\\Column' => 'Column'],
            children: [$innerContainer],
        );

        $json = json_encode($outerContainer, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        // Outer → inner → leaf
        $this->assertSame('row', $decoded['containerType']);
        $this->assertCount(1, $decoded['children']);

        $inner = $decoded['children'][0];
        $this->assertSame('column', $inner['containerType']);
        $this->assertCount(1, $inner['children']);

        $deepLeaf = $inner['children'][0];
        $this->assertSame(200, $deepLeaf['id']);
        $this->assertSame('Deep Leaf', $deepLeaf['title']);
        $this->assertArrayNotHasKey('containerType', $deepLeaf);
    }

    public function testColumnNodeIncludesGridSettings(): void
    {
        $gridSettings = [
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ];

        $node = new ElementNode(
            id: 1,
            title: 'Test Column',
            blockSchema: ['typeName' => 'Column', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Column'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Column,
            allowedTypes: null,
            children: [],
            gridSettings: $gridSettings,
        );

        $serialized = $node->jsonSerialize();
        self::assertArrayHasKey('gridSettings', $serialized);
        self::assertSame($gridSettings, $serialized['gridSettings']);
    }

    public function testConstructorRejectsGridSettingsForNonColumnType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gridSettings may only be provided for Column container type');

        new ElementNode(
            id: 99,
            title: 'Row with grid settings',
            blockSchema: ['typeName' => 'Row', 'actions' => ['edit' => '/edit/99'], 'content' => '', 'label' => 'Row'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Row,
            allowedTypes: null,
            children: [],
            gridSettings: ['xs' => ['width' => 12, 'offset' => 0, 'visible' => true]],
        );
    }

    public function testColumnWithNullGridSettingsOmitsGridSettingsKey(): void
    {
        $node = new ElementNode(
            id: 1,
            title: 'Column No Grid',
            blockSchema: ['typeName' => 'Column', 'actions' => ['edit' => '/edit/1'], 'content' => '', 'label' => 'Column'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Column,
            allowedTypes: null,
            children: [],
            gridSettings: null,
        );

        $serialized = $node->jsonSerialize();
        self::assertArrayNotHasKey('gridSettings', $serialized);
    }

    public function testNonColumnNodeOmitsGridSettings(): void
    {
        $node = new ElementNode(
            id: 2,
            title: 'Test Row',
            blockSchema: ['typeName' => 'Row', 'actions' => ['edit' => '/edit/2'], 'content' => '', 'label' => 'Row'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Row,
            allowedTypes: null,
            children: [],
        );

        $serialized = $node->jsonSerialize();
        self::assertArrayNotHasKey('gridSettings', $serialized);
    }

    public function testContainerNodeSerializesChildAreaId(): void
    {
        $node = new ElementNode(
            id: 10,
            title: 'Container',
            blockSchema: ['typeName' => 'ElementSection', 'actions' => ['edit' => '/edit/10'], 'content' => '', 'label' => 'Section'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Section,
            allowedTypes: ['App\\Elements\\Row' => 'Row'],
            children: [],
            childAreaId: 42,
        );

        $data = $node->jsonSerialize();

        $this->assertArrayHasKey('childAreaId', $data);
        $this->assertSame(42, $data['childAreaId']);
    }

    public function testContainerNodeOmitsChildAreaIdWhenNull(): void
    {
        $node = new ElementNode(
            id: 10,
            title: 'Container',
            blockSchema: ['typeName' => 'ElementSection', 'actions' => ['edit' => '/edit/10'], 'content' => '', 'label' => 'Section'],
            obsoleteClassName: null,
            version: 1,
            canDelete: true,
            canPublish: true,
            canUnpublish: false,
            canCreate: true,
            statusFlags: [],
            containerType: ContainerType::Section,
            allowedTypes: ['App\\Elements\\Row' => 'Row'],
            children: [],
        );

        $data = $node->jsonSerialize();

        $this->assertArrayNotHasKey('childAreaId', $data);
    }

    public function testLeafNodeOmitsChildAreaId(): void
    {
        $node = $this->createLeafNode();
        $data = $node->jsonSerialize();

        $this->assertArrayNotHasKey('childAreaId', $data);
    }
}
