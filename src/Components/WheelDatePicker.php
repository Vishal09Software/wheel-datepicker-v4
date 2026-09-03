<?php

namespace NativeUI\WheelDatePicker\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

class WheelDatePicker extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'wheel_date_picker';
    }
}
