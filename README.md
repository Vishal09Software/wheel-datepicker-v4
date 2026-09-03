# nativeui/native-wheel-datepicker

A wheel date picker **EDGE component** for [NativePHP Mobile v4](https://nativephp.com/docs/mobile/4). It renders as real SwiftUI (`Picker(.wheel)`) on iOS and a snapping Compose wheel on Android — not a web view.

Requires NativePHP Mobile **v4** and `nativephp/mobile-ui`.

## Install

Composer require is not enough. Register the plugin, then rebuild so Kotlin/Swift are compiled in.

### From GitHub (before Packagist)

1. Push this folder as its own Git repository and tag a version (`v0.1.0`).
2. In the **app** `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/YOUR_USER/native-wheel-datepicker"
        }
    ]
}
```

3. Then:

```bash
composer require nativeui/native-wheel-datepicker:^0.1
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register nativeui/native-wheel-datepicker
php artisan native:plugin:validate
php artisan native:plugin:list
```

4. Rebuild the native app (`ios` or `android`):

```bash
php artisan native:run ios
# or
php artisan native:run android
```

PHP-only changes hot-reload. Native renderer changes need `native:run` again. Manifest/native path changes may need `php artisan native:install --force`.

### From Packagist

After you submit the GitHub repo to [Packagist](https://packagist.org), drop the `repositories` entry and run:

```bash
composer require nativeui/native-wheel-datepicker
```

Then register and rebuild as above.

### Local path (plugin development)

```json
{
    "repositories": [
        { "type": "path", "url": "packages/nativeui/native-wheel-datepicker" }
    ]
}
```

```bash
composer require nativeui/native-wheel-datepicker:@dev
```

## Usage

```blade
<native:wheel-date-picker
    native:model="birthday"
    label="Birthday"
    title="Select date"
    format="Y-m-d"
    picker-style="compact"
    confirm-label="Done"
    a11y-label="Birthday"
/>
```

```php
use Native\Mobile\Edge\NativeComponent;

class ProfileScreen extends NativeComponent
{
    public string $birthday = '2026-07-16';

    public function render()
    {
        return view('native.profile');
    }
}
```

`native:model` commits when the user taps **Done** (compact) or when you use the footer on inline. Use plain `native:model` or `native:model.live` — `.blur` / `.debounce` are rejected.

### Events

- `_change="method"` — wheel settle (optional; `native:model` already syncs).
- `_done="method"` — **Done**.
- `_cancel="method"` — **Cancel**.

Each handler receives a `string $value` in the picker’s `format` (default `Y-m-d`).

### Year range

```blade
<native:wheel-date-picker year-start="2020" year-end="2035" />
```

Defaults: `config('wheel-datepicker.year_start')` (1990) through current year + 20.

### Format

PHP `date()` tokens (`Y-m-d`) or aliases: `YYYY-MM-DD`, `DD-MM-YYYY`, `MM-DD-YYYY`, and `/` or `.` variants.

### Colors

Omitted keys use `config('wheel-datepicker.theme')`, then `config/native-ui.php` light tokens (`surface`, `outline`, `primary`, …).

```blade
<native:wheel-date-picker
    :colors="[
        'card' => '#FFFFFF',
        'accent' => '#2563EB',
    ]"
/>
```

Keys: `bg`, `card`, `border`, `text`, `muted`, `muted_2`, `accent`.

### Size

Values are density-independent (`dp` / `pt`). They do **not** auto-grow on tablets.

```blade
<native:wheel-date-picker
    :row-height="44"
    :visible-items="5"
    :card-padding="16"
/>
```

`visible-items` is clamped to **3, 5, or 7**. `wheel-height` defaults to `row-height × visible-items`.

`:size="['row_height' => 52, 'visible_items' => 7]"` also works. Per-attribute props override `:size`, which overrides config.

`class="w-full"` only stretches the **trigger field**.

## Config

```bash
php artisan vendor:publish --tag=wheel-datepicker-config
```

Leave `theme` keys `null` to inherit Native UI. Set hex values only to override.

## JavaScript / web view

This plugin has **no bridge API**. Do not import it from Vue/React. Use the EDGE tag in a `NativeComponent`.

## Tests

Pest examples live in `tests/` and are meant to run inside a NativePHP Laravel app (`Native::test()`).

## Publish this folder to Git

From **this directory** (not the host app):

```bash
git init
git add .
git commit -m "Initial NativePHP wheel date picker plugin"
git branch -M main
git remote add origin https://github.com/YOUR_USER/native-wheel-datepicker.git
git push -u origin main
git tag v0.1.0
git push origin v0.1.0
```

Use a Composer package name you own on Packagist. If `nativeui/` is taken, change `"name"` in `composer.json` **before** the first public tag, then `composer require` that name.

## License

MIT
