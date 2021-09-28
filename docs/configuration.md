# Elemental Grid Configuration

## Default settings
Use the following yaml configuration to set defaults;
```yaml
Webmen\ElementalGrid:
    default_column_size: 12
    default_viewport: 'SM'
    css_framework: 'bulma' (currently supported are bootstrap and bulma)
```

## Add settings to rows
It is possbile to extend a row.

* Add a dataextension to Webmen\ElementalGrid\Models\ElementRow
* Copy the template Webmen/ElementalGrid/Models/ElementRow.ss to your theme
