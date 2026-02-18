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
            blockSchema: ['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => ''],
            obsoleteClassName: null,
            version: 1,
            isPublished: false,
            isLiveVersion: false,
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
            blockSchema: ['typeName' => 'ElementSection', 'actions' => ['edit' => '/edit/10'], 'content' => ''],
            obsoleteClassName: null,
            version: 2,
            isPublished: true,
            isLiveVersion: true,
            canDelete: true,
            canPublish: true,
            canUnpublish: true,
            canCreate: true,
            statusFlags: ['modified' => true],
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
        $this->assertSame(['typeName' => 'BaseElement', 'actions' => ['edit' => '/edit/1'], 'content' => ''], $data['blockSchema']);
        $this->assertNull($data['obsoleteClassName']);
        $this->assertSame(1, $data['version']);
        $this->assertFalse($data['isPublished']);
        $this->assertFalse($data['isLiveVersion']);
        $this->assertTrue($data['canDelete']);
        $this->assertTrue($data['canPublish']);
        $this->assertFalse($data['canUnpublish']);
        $this->assertTrue($data['canCreate']);
        $this->assertSame([], $data['statusFlags']);
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
            blockSchema: ['typeName' => 'ElementRow', 'actions' => ['edit' => '/edit/50'], 'content' => ''],
            obsoleteClassName: null,
            version: 1,
            isPublished: false,
            isLiveVersion: false,
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
}
