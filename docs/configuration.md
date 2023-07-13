# Elemental Grid Configuration

## Default settings
Use the following yaml configuration to override defaults;
```yaml
WeDevelop\ElementalGrid\ElementalConfig:
    default_viewport: 'MD'
    use_custom_title_classes: true
    css_framework: 'bootstrap'
    default_title_tag: 'h2'
```

## Add the extension
To use the Grid extension on the page you want in your project, copy the following code and paste it to your extensions.yml

```yaml
Page\That\Uses\Grid:
    extensions:
        - DNADesign\Elemental\Extensions\ElementalPageExtension
```

### Switching between ElementalArea and default Content per page
If you want to have the possibility to switch between a normal Content field or the ElementalArea, place the following extension on the page that also uses the grid:

```yaml
Page\That\Uses\Grid:
    extensions:
        - WeDevelop\ElementalGrid\Extensions\ElementalPageExtension
```

In your template, you can then use the following check to display the right content:

```silverstripe
<% if $UseElementalGrid && $ElementalArea %>
    $ElementalArea
<% else %>
    $Content
<% end_if %>
```

## Supported CSS frameworks
Currently Bulma and Bootstrap (5) are the supported CSS frameworks that can be combined with this module.

## Using TitleTag and TitleClass
To show the TitleTag in the template, use `$TitleTag`. To show the TitleClass (which has to be enabled via the `use_custom_title_classes` property in the yml config), use the variable `$TitleSizeClass` in your .SS-template.

## Customizing the row, section and container CSS classes
There are 3 extension point you can use to update which row, section and container CSS classes will be rendered in the templates.

### Step 1. Extend the ElementRow
In your own project, extend the ElementRow that lives in this module
```yaml
WeDevelop\ElementalGrid\Models\ElementRow:
  extensions:
    - Your\ElementRow\Extension
```

### Step 2. Add the methods
Add these methods to your own ElementRow extension

```php
public function updateSectionClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}

public function updateRowClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}

public function updateContainerClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}
```

## Override ElementRow template
Copy the file `templates/WeDevelop/ElementalGrid/Models/ElementRow.ss` to your own `themes/` folder. For example to:
`themes/default/WeDevelop/ElementalGrid/Models/ElementRow.ss`. You can then edit the template to make it fit your needs.

## Element content
The new ElementContent will now support different mediatypes (images and videos, both YouTube and Vimeo). Built in are
some handy tools that will make sure your videos will look styled out-of-the-box, as well as some JavaScript handlers that
will replace your video-wrapper with the correct iframe. A button will be shown on top of the video to play the Video using
your own styled player, in stead of the default third-party YouTube or Vimeo UI.

### Extending ElementContent

#### Step 1. Extend the ElementContent
In your own project, extend the ElementContent that lives in this module
```yaml
DNADesign\Elemental\Models\ElementContent:
    extensions:
        - App\Extensions\ElementContentExtension
```

#### Step 2. Add the methods
Add these methods to your own ElementContentExtension to update the CSS styling classes that will be applied to your frontend.

```php
public function updateElementClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}

public function updateMediaColumnClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}

public function updateContentClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}

public function updateContentColumnClasses(&$classes)
{
    $classes[] = 'add-your-own-css-class';
}
```

### Add extra aspect ratios to ElementContent
Adding media aspect ratios will add the extra items, not replace the existing items.
```
WeDevelop\Extensions\ElementContentExtension:
  mediaRatios:
    9x16: '9x16'
```

### Add extra column gaps to ElementContent
Adding column gaps will add the extra items, not replace the existing items.
```
WeDevelop\Extensions\ElementContentExtension:
  columnGaps:
    12: '12'
```
