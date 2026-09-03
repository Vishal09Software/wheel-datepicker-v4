<?php

namespace NativeUI\WheelDatePicker\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class AllCallbacksScreen extends NativeComponent
{
    public string $date = '2026-07-16';

    public function onDateChange(string $value): void
    {
        $this->date = $value;
    }

    public function onDateDone(string $value): void
    {
        $this->date = $value;
    }

    public function onDateCancel(string $value): void
    {
        //
    }

    public function render(): View
    {
        return view('wheel-datepicker-test::all-callbacks');
    }
}
