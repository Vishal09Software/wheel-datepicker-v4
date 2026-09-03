<?php

use Native\Mobile\Testing\Native;
use NativeUI\WheelDatePicker\Tests\Fixtures\WheelDatePickerScreen;

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
