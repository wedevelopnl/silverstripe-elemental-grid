# Elemental Grid Configuration

## Default settings
Use the following yaml configuration to set defaults;
```yaml
TheWebmen\ElementalGrid:
  defaultSizeField: 'MD' (xs, sm, md, lg)
  defaultOffsetField: 'MD' (xs, sm, md, lg)
  cssFramework: 'bootstrap' (bootstrap, bulma)
```

## Add settings to rows
It is possbile to extend a row.

* Add a dataextension to TheWebmen\ElementalGrid\Models\ElementRow
* Copy the template TheWebmen/ElementalGrid/Models/ElementRow.ss to your theme

## Nested rows/grids
To create nested rows or grids you need to create a grid element ss the elemental documentation for more information.

## Disallow rows
See the elemental documentation: https://github.com/dnadesign/silverstripe-elemental and add TheWebmen\ElementalGrid\Models\ElementRow to the disallowed_elements
