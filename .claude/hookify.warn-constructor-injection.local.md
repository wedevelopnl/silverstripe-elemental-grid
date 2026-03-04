---
name: warn-constructor-injection
enabled: true
event: file
action: warn
conditions:
  - field: new_text
    operator: regex_match
    pattern: public function __construct\(
  - field: file_path
    operator: regex_match
    pattern: (Elements|Extensions|Controllers)/.*\.php$
---

**Constructor Injection Warning — SilverStripe Limitation**

Classes extending `AdminController`, `LeftAndMain`, `DataObject`, or `BaseElement` **cannot use constructor injection**. SilverStripe instantiates these classes without DI arguments — constructor parameters will silently be `null`.

**Use property injection instead:**

```php
private static array $dependencies = [
    'MyService' => '%$' . MyServiceInterface::class,
];

public MyServiceInterface $MyService;
```

If this is a plain service class (not a Controller/DataObject/Element), constructor injection is fine — but wire it explicitly in YAML with `constructor:` config.
