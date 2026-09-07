# laratribe/native-wheel-datepicker

A wheel date picker **EDGE component** for [NativePHP Mobile v4](https://nativephp.com/docs/mobile/4). It renders as real SwiftUI (`Picker(.wheel)`) on iOS and a snapping Compose wheel on Android — not a web view.

Requires NativePHP Mobile **v4** and `nativephp/mobile-ui`.

## Install

Composer require is not enough. Register the plugin, then rebuild so Kotlin/Swift are compiled in.

```bash
composer require laratribe/native-wheel-datepicker
php artisan vendor:publish --tag=wheel-datepicker-config
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register laratribe/native-wheel-datepicker
php artisan native:plugin:validate
php artisan native:plugin:list
```

Rebuild the native app (`ios` or `android`):

```bash
php artisan native:run ios
# or
php artisan native:run android
```

PHP-only changes hot-reload. Native renderer changes need `native:run` again. Manifest/native path changes may need `php artisan native:install --force`.

### Local path (plugin development)

```json
{
    "repositories": [
        { "type": "path", "url": "packages/laratribe/native-wheel-datepicker" }
    ]
}
```

```bash
composer require laratribe/native-wheel-datepicker:@dev
php artisan vendor:publish --tag=wheel-datepicker-config
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register laratribe/native-wheel-datepicker
php artisan native:plugin:validate
php artisan native:plugin:list
```

## Configuration

Publish the configuration file (optional, to customize defaults):

```bash
php artisan vendor:publish --tag=wheel-datepicker-config
```

This creates `config/wheel-datepicker.php`. Leave `theme` keys `null` to inherit Native UI light tokens (`surface`, `outline`, `primary`, …). Set hex values only to override.

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

`native:model` commits when the user taps **Done** (compact) or when you use the footer on inline. Use plain `native:model` or `native:model.live` — `.blur` / `.debounce` are rejected outright (no silent no-op).

Value is **empty** (`''`) when `value`/`native:model` is left unbound — the wheels still visually center on today, but nothing is committed until the user confirms, so "not selected" stays distinguishable from a real date. Pass `default-to-today` to opt into committing today automatically instead. Theme `null` inherits Native UI light tokens; paste a hex or `:colors` to override.

### Events

```blade
<native:wheel-date-picker
    on-change="handleChange"
    on-done="handleDone"
    on-cancel="handleCancel"
/>
```

- `on-change="method"` — wheel settle (optional; `native:model` already syncs).
- `on-done="method"` — **Done** (fires unconditionally on confirmation).
- `on-cancel="method"` — **Cancel**.

`_change` / `_done` / `_cancel` still work underneath (that's the literal
attribute name the element receives) but `on-*` is the documented, intended
spelling — use it in new code.

Each handler receives a `string $value` in the picker’s `format` (default `Y-m-d`).

> **Note on reverting to the initial date:**
> If a user opens the wheel, scrolls to another year/date, and then scrolls back to the initial date (e.g. `2026`), `native:model` / `on-change` treats it as unchanged (`newValue === oldValue`) and will not fire. Use `on-done="handleDone"` whenever you need logic, validation, or state updates to run on every confirmation, even if the value is unchanged:
>
> ```blade
> <native:wheel-date-picker
>     :value="$birthday"
>     on-done="handleDateConfirmed"
> />
> ```
>
> ```php
> public function handleDateConfirmed(string $date): void
> {
>     $this->birthday = $date;
>     // Runs reliably on every Done tap
> }
> ```

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

### Timezone

`min-date="today"`, `max-date="today"`, and the native "Today" button all
resolve "today" from `timezone` (IANA name, e.g. `Asia/Kolkata`) instead of
PHP's UTC normalization disagreeing with whatever clock the device is set to.
Defaults to `UTC` on both sides if omitted:

```blade
<native:wheel-date-picker max-date="today" timezone="Asia/Kolkata" />
```

### Format

PHP `date()` tokens (`Y-m-d`) or aliases: `YYYY-MM-DD`, `DD-MM-YYYY`, `MM-DD-YYYY`, and `/` or `.` variants.
Only `Y`, `m`, `d`, and separators (`-` `/` `.` ` `) are supported — anything
else (e.g. stray `j`, `n`, `y`) throws immediately in PHP rather than
reaching the device and parsing incorrectly there.

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
