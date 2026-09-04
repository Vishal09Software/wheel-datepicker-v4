<?php

namespace Laratribe\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class InvertedMinMaxDateScreen extends NativeComponent
{
    public function render(): View
    {
        return view('wheel-datepicker-test::inverted-min-max-date');
    }
}
