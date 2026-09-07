# Changelog

## 1.0.1

- **Documentation:** Clarified `on-done` usage to handle confirmation when
  reverting to the initial date (e.g. `2026`), where `on-change`/`native:model`
  drops the event due to no value delta (`newValue === oldValue`).

- **Behavior change:** `value`/`native:model` now defaults to **empty**
  (`''`) when left entirely unbound, not "today" — "not selected" needs to
  stay distinguishable from a real date for forms. The wheels still visually
  center on today for display. Added `default-to-today` to opt back into
  the old auto-today behavior.
- **Fix (correctness):** added a `timezone` prop (default `UTC`). Native
  "Today" buttons and PHP's `today`/`min-date="today"`/`max-date="today"`
  normalization now both resolve from the same configured zone instead of
  native using the device's local clock while PHP assumed UTC.
- **Fix:** Android `Dialog(onDismissRequest = ...)` and iOS `.sheet`
  swipe-to-dismiss / tap-outside now reset the draft selection to the last
  committed value and fire `on_cancel`, matching the explicit Cancel
  button. Previously the sheet just closed, leaving a stale in-progress
  selection for the next time it opened, and never firing cancel.
- **Feature:** `min-date` / `max-date` are now fully enforced on iOS as well
  as Android — day/month/year wheel bindings, the Today button, and the
  month-nav chevrons all clamp into range; the year list is narrowed to fit.
- **Feature:** `locale` now also localizes month names on iOS (previously
  Android-only); wire value still stays in `format` regardless of locale.
- **Fix (reliability):** `format` now only accepts `Y`, `m`, `d`, and
  separators (`-` `/` `.` ` `). Any other token throws in PHP immediately
  instead of reaching a device, being silently forwarded as a literal by
  `nativePattern()`, and producing wrong parsing there.
- Lowered iOS `min_version` from `18.2` to `16.0` (the actual floor for the
  SwiftUI APIs in use, e.g. `.presentationDetents`) — `18.2` excluded a
  large share of real devices for no reason tied to this plugin's code.
- Added `on-change` / `on-done` / `on-cancel` as the documented event
  attribute spelling; `_change` / `_done` / `_cancel` (the literal attribute
  names the element receives) still work underneath.
- **Breaking:** renamed the package from `nativeui/native-wheel-datepicker`
  to `laratribe/native-wheel-datepicker`, and the PHP namespace from
  `NativeUI\WheelDatePicker` to `Laratribe\WheelDatePicker` (Android package
  `com.nativeui.plugins.wheeldatepicker` → `com.laratribe.plugins.wheeldatepicker`).
  Consumers must update their `composer.json` requirement and re-run
  `native:plugin:register`.
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
  instead of `lazy`/`blur`). Confirmed: `.blur`/`.debounce`/`.lazy` are
  rejected outright rather than silently no-op'ing, since this element only
  ever commits on Done — full verification against the actual
  `native:model.*` → attribute translation in `nativephp/mobile` is still
  outstanding since that package isn't public yet.
- Added defensive, matching bounds-checks on the iOS year range for
  consistency with the Android fix.
- Expanded the Pest suite: reversed year-range normalization, invalid
  `picker-style` rejection, size-prop clamping at documented bounds, all
  three callbacks (`on_change`/`on_done`/`on_cancel`) under both the
  `_*` and `on-*` attribute spellings, format-alias resolution,
  empty-by-default and opt-in default-to-today, unsupported/missing format
  token rejection, min/max-date serialization and year narrowing, and the
  min-after-max validation error.
- Added `.gitignore` and removed the `.DS_Store` that was previously
  committed at the repo root.
- Added a GitHub Actions CI workflow (`.github/workflows/ci.yml`):
  composer validate + PHP lint on 8.2/8.3. The Pest run in CI, and any
  Swift/Kotlin-level device tests, are still best-effort/outstanding since
  `nativephp/mobile`/`mobile-ui` aren't public and this environment has no
  Xcode/Android SDK to run real on-device tests against — that gap is
  tracked as a known limitation, not yet fixed.
- Declared `pestphp/pest` as a `require-dev` dependency and added a
  `composer test` script.

## 0.1.0

- Initial public release: compact/inline native wheel date picker for NativePHP Mobile v4.
- EDGE tag `<native:wheel-date-picker>` with `native:model`, PHP date formats, size knobs, and Native UI theme colours.
