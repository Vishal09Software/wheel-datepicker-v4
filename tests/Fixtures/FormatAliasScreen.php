<?php

namespace NativeUI\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class FormatAliasScreen extends NativeComponent
{
    public string $date = '16-07-2026';

    public function render(): View
    {
        return view('wheel-datepicker-test::format-alias');
    }
}
