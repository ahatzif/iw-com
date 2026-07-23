# COM Theme

Starter WordPress theme for project-specific builds.

The source namespace is `project`. During site scaffolding, the installer should
replace it with the project code:

```php
com\theme::load_template_part(...)
```

becomes:

```php
bmw\theme::load_template_part(...)
com\theme::load_template_part(...)
```

The starter keeps temporary compatibility aliases for old `IW_Theme` references
while plugins and imported blocks are migrated.

## Build Assets

```sh
cd assets
npm install
npm run build
```

## Migration Notes

- `IW_Theme` is now `com\theme`.
- `IW_Theme_Config` is now `com\config`.
- Theme text domain and image-size slugs use `com-theme`.
- Old plugin references are intentionally migrated in a second pass after the
  reusable plugin namespace is finalized.
