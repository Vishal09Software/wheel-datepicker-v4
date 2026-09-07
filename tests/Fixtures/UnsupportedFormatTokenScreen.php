<?php

namespace Laratribe\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class UnsupportedFormatTokenScreen extends NativeComponent
{
    public function render(): View
    {
        return view('wheel-datepicker-test::unsupported-format-token');
    }
}
