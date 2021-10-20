# Elemental Grid Configuration

## Default settings
Use the following yaml configuration to override defaults;
```yaml
TheWebmen\ElementalGrid\Config:
    default_viewport: 'MD'
    use_custom_title_classes: true'
    css_framework: 'bulma'
```

## Set the right extensions
To use the Grid extension on the page you want in your project, copy the following code and paste it to your extensions.yml

```yaml
Page\That\Uses\Grid:
    extensions:
        - DNADesign\Elemental\Extensions\ElementalPageExtension
```

Note: if you want to have the possibility to switch between a normal Content field or the ElementalArea, place the following extension on the page that also uses the grid:

```yaml
Page\That\Uses\Grid:
    extensions:
        - TheWebmen\ElementalGrid\Extensions\ElementalPageExtension
```

## Supported CSS frameworks
Currently Bulma and Bootstrap (5) are the supported CSS frameworks that can be combined with this module.

## Add settings to rows
It is possbile to extend a row.

* Add a dataextension to TheWebmen\ElementalGrid\Models\ElementRow
* Copy the template TheWebmen/ElementalGrid/Models/ElementRow.ss to your theme
