<?php

namespace WeDevelop\Resolvers;

use DNADesign\Elemental\GraphQL\Resolvers\Resolver;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Services\ReorderElements;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;

class ElementalResolver extends Resolver {
    public static function resolveAddElementToArea(
        $obj,
        array $args,
        array $context,
        ResolveInfo $info
    ): BaseElement {
        $elementClass = $args['className'];
        $elementalAreaID = $args['elementalAreaID'];
        $afterElementID = $args['afterElementID'] ?? null;
        $insertAtBottom = $args['insertAtBottom'] ?? null;

        if (!is_subclass_of($elementClass, BaseElement::class)) {
            throw new InvalidArgumentException("$elementClass is not a subclass of " . BaseElement::class);
        }

        $elementalArea = ElementalArea::get()->byID($elementalAreaID);

        if (!$elementalArea) {
            throw new InvalidArgumentException("Invalid ElementalAreaID: $elementalAreaID");
        }

        $member = UserContextProvider::get($context);
        if (!$elementalArea->canEdit($member)) {
            throw new InvalidArgumentException("The current user has insufficient permission to edit ElementalAreas");
        }

        /** @var BaseElement $newElement */
        $newElement = Injector::inst()->create($elementClass);

        $member = UserContextProvider::get($context);
        if (!$newElement->canEdit($member)) {
            throw new InvalidArgumentException(
                'The current user has insufficient permission to edit Elements'
            );
        }

        // Assign the parent ID directly rather than via HasManyList to prevent multiple writes.
        // See BaseElement::$has_one for the "Parent" naming.
        $newElement->ParentID = $elementalArea->ID;
        // Ensure that a sort order is assigned - see BaseElement::onBeforeWrite()
        $newElement->onBeforeWrite();

        if ($insertAtBottom) {
            $lastElement = $elementalArea->Elements()->sort('Sort')->last();

            if ($lastElement) {
                $afterElementID = $lastElement->ID;
            }
        }

        if ($afterElementID !== null) {
            /** @var ReorderElements $reorderer */
            $reorderer = Injector::inst()->create(ReorderElements::class, $newElement);
            $reorderer->reorder($afterElementID); // also writes the element
        } else {
            $newElement->write();
        }

        return $newElement;
    }
}
