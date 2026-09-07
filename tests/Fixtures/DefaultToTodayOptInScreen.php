<?php

namespace Laratribe\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class DefaultToTodayOptInScreen extends NativeComponent
{
    public function render(): View
    {
        return view('wheel-datepicker-test::default-to-today-opt-in');
    }
}
