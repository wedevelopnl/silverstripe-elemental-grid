# Elemental Grid Configuration

## Default settings
Use the following yaml configuration to set defaults;
```yaml
TheWebmen\ElementalGrid:
    default_viewport: 'SM'
    css_framework: 'bulma'
```
## Supported CSS frameworks
Currently Bulma and Bootstrap (5) are the supported CSS frameworks that can be combined with this module.

## Add settings to rows
It is possbile to extend a row.

* Add a dataextension to TheWebmen\ElementalGrid\Models\ElementRow
* Copy the template TheWebmen/ElementalGrid/Models/ElementRow.ss to your theme
