<?php

namespace Laratribe\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class ReversedYearRangeScreen extends NativeComponent
{
    public string $date = '2026-07-16';

    public function render(): View
    {
        return view('wheel-datepicker-test::reversed-years');
    }
}
