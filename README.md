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

## Settings
TWM\ElementalGrid\Extensions\BaseElementExtension num_columns (default: 12)

## Add settings to rows
* Add a dataextension to TWM\ElementalGrid\Models\ElementRow
* Copy the template TWM/ElementalGrid/Models/ElementRow.ss to your theme

## Disallow rows
See the elemental documentation: https://github.com/dnadesign/silverstripe-elemental and add TWM\ElementalGrid\Models\ElementRow to the disallowed_elements
