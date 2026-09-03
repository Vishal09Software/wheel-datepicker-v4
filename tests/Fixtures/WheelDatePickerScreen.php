<?php

namespace NativeUI\WheelDatePicker\Tests\Fixtures;

use Native\Mobile\Edge\NativeComponent;

class WheelDatePickerScreen extends NativeComponent
{
    public string $date = '2026-07-16';

    public function onDateDone(string $value): void
    {
        $this->date = $value;
    }

    public function render()
    {
        return view('wheel-datepicker-test::screen');
    }
}
