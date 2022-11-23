# Upgrading from v2 to v3

You can follow the following steps to update Elemental 2.x to elemental 3.x

## Upgrade steps
* Make sure you've updated the package to 3.x using Composer
* Update all [Configuration](configuration.md) references to the new namespace
  * `TheWebmen\ElementalGrid\ElementalConfig` => `WeDevelop\ElementalGrid\ElementalConfig`
  * (if used) `TheWebmen\ElementalGrid\Models\ElementRow` => `WeDevelop\ElementalGrid\Models\ElementRow`
  * (if used ) `TheWebmen\ElementalGrid\Extensions\ElementalPageExtension` => `WeDevelop\ElementalGrid\Extensions\ElementalPageExtension` => `WeDevelop\ElementalGrid\Extensions\ElementalPageExtension`
* If you have local template overrides for `TheWebmen/ElementalGrid/Models/ElementRow.ss` you need to update the folder name to `WeDevelop/ElementalGrid/Models/ElementRow.ss`.
* Run an `dev/build`
* Run the following task to migrate the namespace for `ElementRow` in the database
  * `php vendor/silverstripe/framework/cli-script.php dev/tasks/migrate-elemental-grid-namespace`
* You're ready to go!