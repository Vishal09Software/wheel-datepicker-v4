# laratribe/native-wheel-datepicker

A wheel date picker **EDGE component** for [NativePHP Mobile v4](https://nativephp.com/docs/mobile/4). It renders as real SwiftUI (`Picker(.wheel)`) on iOS and a snapping Compose wheel on Android — not a web view.

Requires NativePHP Mobile **v4** and `nativephp/mobile-ui`. Run every command below in your **NativePHP Laravel app**, not in this plugin folder.

## Install and run

Composer require is not enough. You must register the plugin, then rebuild so Kotlin and Swift are compiled into the app.

### 1. Require the package

```bash
composer require laratribe/native-wheel-datepicker
```

### 2. Publish NativePHP's plugin provider (once per app)

Creates `app/Providers/NativeServiceProvider.php` if it does not exist. Required before register.

```bash
php artisan vendor:publish --tag=nativephp-plugins-provider
```

### 3. Register the plugin

Laravel autoloads the PHP. Native Swift/Kotlin is only compiled after you register — this is a security gate so transitive Composer packages cannot silently ship native code.

```bash
php artisan native:plugin:register laratribe/native-wheel-datepicker
```

Confirm it is wired up:

```bash
php artisan native:plugin:validate
php artisan native:plugin:list
```

You should see `laratribe/native-wheel-datepicker` in the list.

### 4. Rebuild and run the native app

```bash
php artisan native:run ios
# or
php artisan native:run android
```

| What changed | What to run |
| --- | --- |
| PHP / Blade only | Nothing extra — hot-reloads |
| Kotlin / Swift renderer | `php artisan native:run ios` or `android` |
| `nativephp.json` or native file paths | `php artisan native:install --force`, then `native:run` |

### Local path (plugin development)

Clone or copy this repo into the app (for example `packages/laratribe/native-wheel-datepicker`), require `@dev`, then register and rebuild as in steps 2–4:

```bash
composer require laratribe/native-wheel-datepicker:@dev
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register laratribe/native-wheel-datepicker
php artisan native:run ios
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

Default value is **today** when `value`/`native:model` is left unbound; theme `null` inherits Native UI light tokens, paste a hex or `:colors` to override.

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

### Min / max date

`year-start`/`year-end` only bound the year wheel. To cap the actual selectable
day — "not after today", "18 years ago or earlier", a booking window, etc. —
use `min-date` / `max-date` instead. They accept the same formats as `value`,
including `today`, and are enforced down to the day on both platforms:

```blade
<native:wheel-date-picker max-date="today" />

<native:wheel-date-picker min-date="{{ now()->subYears(18)->toDateString() }}" max-date="today" />
```

If `year-start`/`year-end` are also set, they're narrowed to fit inside
`min-date`/`max-date` automatically so the year wheel can never land on a year
the day/month wheels would then clamp back out of. `min-date` after `max-date`
throws.

### Format

PHP `date()` tokens (`Y-m-d`) or aliases: `YYYY-MM-DD`, `DD-MM-YYYY`, `MM-DD-YYYY`, and `/` or `.` variants.

### Locale

`locale` only changes the month names shown on the drum (`en`, `fr-FR`, `ja`,
…). The value committed over the bridge always stays in `format` (default
`Y-m-d`), so parsing on the PHP side never has to account for locale:

```blade
<native:wheel-date-picker locale="fr-FR" />
```

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

Copy-paste starting point for a host app:

```php
use Native\Mobile\Testing\Native;

it('shows today as the default and commits the picked date', function () {
    Native::test(ProfileScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            return $node['props']['value'] === now()->format('Y-m-d');
        });
});
```

## License

MIT
