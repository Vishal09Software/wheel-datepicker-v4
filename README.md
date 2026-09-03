# nativeui/native-wheel-datepicker

A dark, iOS-style wheel date picker as a genuine **native EDGE component** for
**NativePHP Mobile v4** — rendered by real SwiftUI (`Picker(.wheel)`) on iOS and
a hand-rolled snapping wheel in Jetpack Compose on Android. No web view, no
HTML/CSS/JS: it's a `<native:wheel-date-picker>` tag backed by native code,
built the same way NativePHP's own `nativephp/mobile-ui` components are.

This is a **UI Component Plugin**, not a nested/composed component — a wheel
picker doesn't exist in EDGE's built-in element set, so it needs its own
Swift `View` and Kotlin `@Composable`, wired up via `nativephp.json`.

## How this differs from a webview picker

| | Webview version | This plugin |
|---|---|---|
| Renders via | HTML/CSS/JS in a `<webview>` | Native SwiftUI / Jetpack Compose |
| Ships as | Plain Composer package (`Blade::component()`) | NativePHP **plugin** (`type: nativephp-plugin`) |
| Needs a build step | No | **Yes** — native code is compiled in at `php artisan native:run` |
| Wheel scrolling | JS scroll-snap | `Picker(.wheel)` (iOS) / snap-fling `LazyColumn` (Android) |

## Install

1. Copy this folder into your NativePHP app, e.g. `packages/nativeui/native-wheel-datepicker`.
2. In your app's root `composer.json`:
   ```json
   "repositories": [
       { "type": "path", "url": "packages/nativeui/native-wheel-datepicker" }
   ]
   ```
3. Require it and register it as a plugin:
   ```bash
   composer require nativeui/native-wheel-datepicker:@dev
   php artisan native:plugin:register nativeui/native-wheel-datepicker
   php artisan native:plugin:validate
   ```
4. Rebuild the native projects so the Swift/Kotlin renderers get compiled in
   (adding a component to the manifest and only restarting PHP renders nothing):
   ```bash
   php artisan native:run
   ```
   If you're iterating on the native code itself, you may need a fresh install
   of the native projects per NativePHP's plugin docs.

## Usage

```blade
<native:wheel-date-picker
    value="{{ $birthday }}"
    title="Select date"
    _done="onBirthdaySelected"
/>
```

```php
class ProfileScreen extends NativeComponent
{
    public string $birthday = '2026-07-16';

    public function onBirthdaySelected(string $value): void
    {
        $this->birthday = $value; // "2026-07-16"
    }
}
```

### Events

- `_change="method"` — fires on every wheel settle (day/month/year, or the
  month-header prev/next arrows), with the ISO `Y-m-d` value.
- `_done="method"` — fires when the user taps **Done**.
- `_cancel="method"` — fires when the user taps **Cancel**.

All three receive a single `string $value` argument (ISO date).

### Year range

```blade
<native:wheel-date-picker year-start="2020" year-end="2035" />
```

Falls back to `config('wheel-datepicker.year_start')` / `year_end` (default
1990 → current year + 20) when omitted.

### Hide the footer

```blade
<native:wheel-date-picker :show-footer="false" />
```

Useful if you're driving Today/Cancel/Done from your own `<native:button>`s
around the picker instead.

### Colors

```blade
<native:wheel-date-picker
    :colors="[
        'bg' => '#1a1025',
        'card' => '#241735',
        'accent' => '#c084fc',
    ]"
/>
```

Available keys: `bg`, `card`, `border`, `text`, `muted`, `muted_2`, `accent`.
Omitted keys fall back to `config('wheel-datepicker.theme')`, which itself
falls back to the built-in dark theme. Set the theme array once in
`config/wheel-datepicker.php` to skin every picker in your app.

## Config

```bash
php artisan vendor:publish --tag=wheel-datepicker-config
```

```php
// config/wheel-datepicker.php
return [
    'year_start' => 1990,
    'year_end' => now()->year + 20,
    'theme' => [
        'bg' => '#0e1324',
        'accent' => '#7c9bff',
        // ...
    ],
];
```

## Package layout

```
native-wheel-datepicker/
├── composer.json                          # type: "nativephp-plugin"
├── nativephp.json                         # manifest — declares the `wheel_date_picker` component
├── config/wheel-datepicker.php
├── src/
│   ├── WheelDatePickerServiceProvider.php
│   ├── Elements/WheelDatePicker.php       # PHP element — props → wire node
│   └── Components/WheelDatePicker.php     # Blade component — tag → element
├── resources/
│   ├── android/WheelDatePickerRenderer.kt # Compose renderer
│   └── ios/WheelDatePickerRenderer.swift  # SwiftUI renderer
└── tests/
    ├── WheelDatePickerTest.php
    └── Fixtures/
```

## A note on accuracy

This was built directly from NativePHP v4's published **UI Component Plugin**
architecture (Element → Blade component → Kotlin renderer → Swift renderer,
wired through `nativephp.json`), matching the shape of the scaffold
`php artisan native:plugin:create` generates. The PHP half (`Element`,
`CallbackRegistry`, Blade component, testing via `Native::test()`) follows the
documented API closely.

The **native renderer bodies** (the actual Compose/SwiftUI layout code) are
original implementations written for this picker — they aren't copied from
NativePHP's source, since that isn't public. The typed prop accessors used
(`getString`, `getInt`, `getBool`, `getColor`, `getCallbackId`) and the event
bridge calls (`NativeUIBridge.sendTextChangeEvent`, `NativeElementBridge.sendTextChangeEvent`)
are used exactly as shown in NativePHP's docs, but I haven't compiled this
against the actual SDK. Before shipping:

1. Run `php artisan native:plugin:validate`.
2. Run `php artisan native:run` and open both platforms — check that
   `getColor`'s exact signature (e.g. its default-value type) matches what
   your installed `nativephp/mobile-air` version expects, and adjust the two
   renderer files if the compiler flags a mismatch.
3. Confirm `NativeComponent` and `Native\Mobile\Edge\NativeComponent` are the
   correct namespaces for your installed version — these moved around during
   the v4 betas.
