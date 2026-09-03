<?php

namespace NativeUI\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class InvalidPickerStyleScreen extends NativeComponent
{
    public string $date = '2026-07-16';

    public function render(): View
    {
        return view('wheel-datepicker-test::invalid-picker-style');
    }
}
