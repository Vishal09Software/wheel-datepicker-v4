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

Commits on Done. Use `native:model` or `native:model.live` only.

Optional: `year-start`, `year-end`, `min-date`, `max-date`, `locale`, `:colors`, `:row-height`, `:visible-items` (3, 5, or 7), `:size`.

`value`/`native:model` defaults to today when left unbound. `min-date`/`max-date` clamp the actual day (e.g. `max-date="today"`), not just the year wheel.

Publish config: `php artisan vendor:publish --tag=wheel-datepicker-config`. Theme keys inherit `config/native-ui.php` when null.

### JavaScript

There is no JavaScript API. Do not import this package from Vue or React.
