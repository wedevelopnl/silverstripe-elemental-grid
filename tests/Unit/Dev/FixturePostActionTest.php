<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Dev;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Dev\FixturePostAction;

#[CoversClass(FixturePostAction::class)]
final class FixturePostActionTest extends TestCase
{
    public function testApplyThrowsWhenPublishRecursiveCalledOnNonVersionedRecord(): void
    {
        $action = new FixturePostAction(
            action: 'publish_recursive',
            class: DataObject::class,
            identifier: 'test',
        );

        $record = $this->createMock(DataObject::class);
        $record->method('hasExtension')->with(Versioned::class)->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post-action "publish_recursive" requires Versioned extension');

        $action->apply($record);
    }

    public function testApplyThrowsWhenUnpublishCalledOnNonVersionedRecord(): void
    {
        $action = new FixturePostAction(
            action: 'unpublish',
            class: DataObject::class,
            identifier: 'test',
        );

        $record = $this->createMock(DataObject::class);
        $record->method('hasExtension')->with(Versioned::class)->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post-action "unpublish" requires Versioned extension');

        $action->apply($record);
    }

    public function testApplyModifyDoesNotRequireVersionedExtension(): void
    {
        $action = new FixturePostAction(
            action: 'modify',
            class: DataObject::class,
            identifier: 'test',
            fields: ['Title' => 'Updated'],
        );

        $record = $this->createMock(DataObject::class);
        $record->expects($this->never())->method('hasExtension');
        $record->expects($this->once())->method('setField')->with('Title', 'Updated');
        $record->expects($this->once())->method('write');

        $action->apply($record);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function versionedActionsProvider(): array
    {
        return [
            'publish_recursive' => ['publish_recursive'],
            'unpublish' => ['unpublish'],
        ];
    }

    public function testFromConfigThrowsWhenClassMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires "action", "class", and "identifier" keys');

        FixturePostAction::fromConfig([
            'action' => 'publish_recursive',
            'identifier' => 'x',
        ]);
    }

    public function testFromConfigThrowsWhenIdentifierMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires "action", "class", and "identifier" keys');

        FixturePostAction::fromConfig([
            'action' => 'publish_recursive',
            'class' => 'SomeClass',
        ]);
    }

    #[DataProvider('versionedActionsProvider')]
    public function testApplyErrorMessageIncludesRecordClass(string $actionName): void
    {
        $action = new FixturePostAction(
            action: $actionName,
            class: DataObject::class,
            identifier: 'test',
        );

        $record = $this->createMock(DataObject::class);
        $record->method('hasExtension')->with(Versioned::class)->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/but MockObject_DataObject_\w+ does not have it/');

        $action->apply($record);
    }
}
