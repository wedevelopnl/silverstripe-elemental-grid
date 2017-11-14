# SilverStripe Elemental Grid

## Introduction

This module converts the elemental module (https://github.com/dnadesign/silverstripe-elemental) into a grid module

![Overview](docs/images/screen01.png)

## Requirements

* SilverStripe CMS ^4.0
* dnadesign/silverstripe-elemental dev-master

## Installation

```
composer require "twm/silverstripe-elemental-grid" "dev-master"
```

## Frontend
Create the template: theme/templates/ElementHolder.ss with the following content:
```
<div class="element $Element.BootstrapColClasses $SimpleClassName.LowerCase<% if $StyleVariant %> $StyleVariant<% end_if %><% if $ExtraClass %> $ExtraClass<% end_if %>" id="$Anchor">
    <% uncached %>
    <% if canEdit && isCMSPreview == 0 %>
        <div style="position: relative">
            <a href="$CMSEditLink" style="position: absolute; right: 0; top: 0; padding: 5px 10px; background: #0071c4; color: white;">Edit</a>
        </div>
    <% end_if %>
    <% end_uncached %>
    $Element
</div>
```
And create the following template: theme/templates/DNADesign/Elemental/Models/ElementalArea.ss with the following content:
```
<% if $ElementControllersWithRows %>
    <% loop $ElementControllersWithRows %>
        $Me
    <% end_loop %>
<% end_if %>
```

## Settings
TWM\ElementalGrid\Extensions\BaseElementExtension num_columns (default: 12)

## Disallow rows
See the elemental documentation: https://github.com/dnadesign/silverstripe-elemental and add TWM\ElementalGrid\Models\ElementRow to the disallowed_elements

## TODO
- Improve docs/readme:
    - Add options to rows section
    - Add screenshots
- Responsive options
- Offset options
- Keep the templates in the module
