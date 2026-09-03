# Changelog

## Unreleased

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
  three callbacks (`on_change`/`on_done`/`on_cancel`), and format-alias
  resolution.
- Added `.gitignore` and removed a committed `.DS_Store`.
- Added a CI workflow (composer validate + PHP lint on 8.2/8.3; Pest run
  is best-effort until `nativephp/mobile`/`mobile-ui` are public).
- Declared `pestphp/pest` as a `require-dev` dependency and added a
  `composer test` script.

## 0.1.0

- Initial public release: compact/inline native wheel date picker for NativePHP Mobile v4.
- EDGE tag `<native:wheel-date-picker>` with `native:model`, PHP date formats, size knobs, and Native UI theme colours.
