## laratribe/native-wheel-datepicker

Native EDGE wheel date picker for NativePHP Mobile v4. Not a web view. Not a JS bridge.

### Installation

```bash
composer require laratribe/native-wheel-datepicker
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register laratribe/native-wheel-datepicker
```

Then rebuild: `php artisan native:run ios` or `php artisan native:run android`.

If the package is not on Packagist yet, add a Composer `vcs` repository pointing at the GitHub URL first.

### PHP / Blade (NativeComponent)

@verbatim
<code-snippet name="Wheel date picker with native:model" lang="blade">
<native:wheel-date-picker
    native:model="birthday"
    label="Birthday"
    title="Select date"
    format="Y-m-d"
    picker-style="compact"
    confirm-label="Done"
    a11y-label="Birthday"
/>
</code-snippet>
@endverbatim

Commits on Done. Use `native:model` or `native:model.live` only — `.blur`/`.debounce`/`.lazy` throw immediately.

Optional: `year-start`, `year-end`, `min-date`, `max-date`, `timezone`, `locale`, `default-to-today`, `:colors`, `:row-height`, `:visible-items` (3, 5, or 7), `:size`. Events: `on-change`, `on-done`, `on-cancel`. Use `on-done` to ensure handlers fire on confirmation even when reverting to the initial value (where `on-change` treats it as unchanged).

`value`/`native:model` is **empty** when left unbound (wheels still show today visually; nothing commits until confirmed) — pass `default-to-today` to auto-commit today instead. `min-date`/`max-date` clamp the actual day (e.g. `max-date="today"`, `timezone="Asia/Kolkata"`), not just the year wheel. `format` only supports `Y`/`m`/`d` tokens — anything else throws.

Publish config: `php artisan vendor:publish --tag=wheel-datepicker-config`. Theme keys inherit `config/native-ui.php` when null.

### JavaScript

There is no JavaScript API. Do not import this package from Vue or React.
