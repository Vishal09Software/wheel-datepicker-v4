<?php

use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Testing\Native;
use Laratribe\WheelDatePicker\Elements\WheelDatePicker;
use Laratribe\WheelDatePicker\Tests\Fixtures\AllCallbacksScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\ClampedSizeScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\DefaultTodayScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\FormatAliasScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\InvalidPickerStyleScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\InvertedMinMaxDateScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\MinMaxDateScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\ReversedYearRangeScreen;
use Laratribe\WheelDatePicker\Tests\Fixtures\WheelDatePickerScreen;

beforeEach(function () {
    $this->app['view']->addNamespace(
        'wheel-datepicker-test',
        __DIR__.'/Fixtures/views'
    );
});

it('renders the wheel date picker with the props the screen passed', function () {
    Native::test(WheelDatePickerScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            return $node['props']['value'] === '2026-07-16'
                && $node['props']['title'] === 'Select date'
                && $node['props']['year_start'] === 2020
                && $node['props']['year_end'] === 2035;
        });
});

it('registers the done callback so it resolves to an integer id', function () {
    Native::test(WheelDatePickerScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            return is_int($node['props']['on_done']);
        });
});

it('serializes density-independent size defaults from config', function () {
    Native::test(WheelDatePickerScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return $props['row_height'] === 44
                && $props['visible_items'] === 5
                && $props['wheel_height'] === 220;
        });
});

it('inherits Native UI theme colours when plugin theme keys are empty', function () {
    Native::test(WheelDatePickerScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];
            $theme = config('native-ui.theme.light');

            return $props['color_card'] === TailwindParser::resolveColorValue($theme['surface'])
                && $props['color_accent'] === TailwindParser::resolveColorValue($theme['primary']);
        });
});

it('normalizes a reversed year range instead of shipping it to native renderers', function () {
    // Regression test: Android's wheel renderer crashes with
    // IllegalArgumentException when year_start > year_end produces an empty
    // year list. The element must swap the values so year_start <= year_end
    // always holds, whatever order the attributes were written in.
    Native::test(ReversedYearRangeScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return $props['year_start'] === 2020
                && $props['year_end'] === 2035
                && $props['year_start'] <= $props['year_end'];
        });
});

it('throws for an invalid picker-style value', function () {
    Native::test(InvalidPickerStyleScreen::class);
})->throws(InvalidArgumentException::class, 'picker-style` must be one of [compact, inline]');

it('clamps size props to their documented bounds', function () {
    Native::test(ClampedSizeScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return $props['visible_items'] === 7   // 9 clamps down to the nearest allowed value
                && $props['row_height'] === 80      // 500 clamps to the max
                && $props['selected_font'] === 32   // 100 clamps to the max
                && $props['muted_font'] === 10       // 1 clamps to the min
                && $props['card_padding'] === 48;    // 999 clamps to the max
        });
});

it('registers change, done, and cancel callbacks as integer ids', function () {
    Native::test(AllCallbacksScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return is_int($props['on_change'])
                && is_int($props['on_done'])
                && is_int($props['on_cancel']);
        });
});

it('resolves a human-friendly format alias to its PHP date() equivalent and native pattern', function () {
    Native::test(FormatAliasScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return $props['format'] === 'd-m-Y'
                && $props['pattern'] === 'dd-MM-yyyy'
                && $props['value'] === '16-07-2026';
        });
});

it('rejects sync modes other than live with a directive-specific message', function () {
    expect(fn () => WheelDatePicker::make()->syncMode('blur'))
        ->toThrow(InvalidArgumentException::class, 'native:model.blur` sync mode');

    expect(fn () => WheelDatePicker::make()->syncMode('lazy'))
        ->toThrow(InvalidArgumentException::class, 'native:model.lazy` sync mode');

    expect(fn () => WheelDatePicker::make()->syncMode('debounce'))
        ->toThrow(InvalidArgumentException::class, 'native:model.debounce.Xms` sync mode');

    expect(WheelDatePicker::make()->syncMode('live'))->toBeInstanceOf(WheelDatePicker::class);
});

it('defaults value to today when no value is bound at all', function () {
    // Normalization runs in UTC (see WheelDatePicker::normalize()), so assert
    // against a UTC "today" rather than the app timezone to avoid flakiness
    // right around midnight in non-UTC test environments.
    $today = (new DateTimeImmutable('today', new DateTimeZone('UTC')))->format('Y-m-d');

    Native::test(DefaultTodayScreen::class)
        ->assertElement('wheel_date_picker', function ($node) use ($today) {
            return $node['props']['value'] === $today;
        });
});

it('serializes min-date, max-date, and locale, and narrows year-start/year-end to match', function () {
    Native::test(MinMaxDateScreen::class)
        ->assertElement('wheel_date_picker', function ($node) {
            $props = $node['props'];

            return $props['min_date'] === '2000-01-01'
                && $props['max_date'] === '2026-12-31'
                && $props['locale'] === 'fr-FR'
                // year-start/year-end were 1990/2040 but must be pulled in to
                // match the full-date bounds so the year wheel can't land on
                // a year the day/month wheels would then clamp out of.
                && $props['year_start'] === 2000
                && $props['year_end'] === 2026;
        });
});

it('throws when min-date is after max-date', function () {
    Native::test(InvertedMinMaxDateScreen::class);
})->throws(InvalidArgumentException::class, 'min-date` [2026-12-31] must not be after `max-date` [2000-01-01]');

