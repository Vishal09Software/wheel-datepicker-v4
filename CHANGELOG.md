# Changelog

## Unreleased

- **Breaking:** renamed the package from `nativeui/native-wheel-datepicker`
  to `laratribe/native-wheel-datepicker`, and the PHP namespace from
  `NativeUI\WheelDatePicker` to `Laratribe\WheelDatePicker` (Android package
  `com.nativeui.plugins.wheeldatepicker` → `com.laratribe.plugins.wheeldatepicker`).
  Consumers must update their `composer.json` requirement and re-run
  `native:plugin:register`.
- **Feature:** added `min-date` / `max-date`. Unlike `year-start`/`year-end`,
  these clamp down to the day on both platforms, so "not after today",
  "18 years ago or earlier", or a fixed booking window can be expressed
  directly. When both are set, `year-start`/`year-end` are automatically
  narrowed to fit inside them; `min-date` after `max-date` throws.
- **Feature:** added `locale` (BCP-47, e.g. `fr-FR`, `ja`) to localize the
  month names shown on the drum. The value committed over the bridge is
  unaffected and always stays in `format` (default `Y-m-d`).
- **Behavior change:** `value`/`native:model` now defaults to **today**
  when left entirely unbound. An explicit `value=""` still renders the
  placeholder and is not overridden.
- Moved native renderer sources into `resources/android/src/main/kotlin/…`
  and `resources/ios/Sources/` instead of loose files directly under
  `resources/android` / `resources/ios`.
- Dropped `"minimum-stability": "dev"` from `composer.json` now that this
  is a public package; consumers no longer need `minimum-stability: dev`
  in their own app to require it.
- **Fix (crash):** normalize `year-start`/`year-end` in the PHP element so
  a reversed range (e.g. `year-start` accidentally greater than `year-end`)
  can no longer reach the Android renderer as an empty year list, which
  previously threw `IllegalArgumentException`.
- **Fix:** Android's "jump to today" and initial year-wheel positioning now
  clamp to the nearest boundary year instead of silently jumping to the
  first year in the list when the current value falls outside the
  configured range.
- **Fix:** `syncMode()` no longer suggests the wrong Blade directive (e.g.
  it previously told `native:model.lazy` users to use `debounce.Xms`
  instead of `lazy`/`blur`).
- Added defensive, matching bounds-checks on the iOS year range for
  consistency with the Android fix.
- Expanded the Pest suite: reversed year-range normalization, invalid
  `picker-style` rejection, size-prop clamping at documented bounds, all
  three callbacks (`on_change`/`on_done`/`on_cancel`), format-alias
  resolution, default-to-today, min/max-date serialization and year
  narrowing, and the min-after-max validation error.
- Added `.gitignore` and removed the `.DS_Store` that was previously
  committed at the repo root.
- Added a GitHub Actions CI workflow (`.github/workflows/ci.yml`):
  composer validate + PHP lint on 8.2/8.3; the Pest run in CI is
  best-effort until `nativephp/mobile`/`mobile-ui` are public, since the
  suite needs them to boot the testing app — run `composer test` locally
  against a real NativePHP app in the meantime.
- Declared `pestphp/pest` as a `require-dev` dependency and added a
  `composer test` script.

## 0.1.0

- Initial public release: compact/inline native wheel date picker for NativePHP Mobile v4.
- EDGE tag `<native:wheel-date-picker>` with `native:model`, PHP date formats, size knobs, and Native UI theme colours.
