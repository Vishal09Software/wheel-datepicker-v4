<?php

namespace NativeUI\WheelDatePicker\Elements;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class WheelDatePicker extends Element
{
    protected string $type = 'wheel_date_picker';

    protected array $componentProps = [];

    public static function make(): static
    {
        return new static;
    }

    public function value(string $value): static
    {
        $this->componentProps['value'] = $value;

        return $this;
    }

    public function title(string $title): static
    {
        $this->componentProps['title'] = $title;

        return $this;
    }

    public function yearStart(int $year): static
    {
        $this->componentProps['year_start'] = $year;

        return $this;
    }

    public function yearEnd(int $year): static
    {
        $this->componentProps['year_end'] = $year;

        return $this;
    }

    public function showFooter(bool $show = true): static
    {
        $this->componentProps['show_footer'] = $show;

        return $this;
    }

    /**
     * @param  array<string, string>  $colors  Any of: bg, card, border, text, muted, muted_2, accent.
     */
    public function colors(array $colors): static
    {
        foreach ($colors as $key => $color) {
            $this->componentProps['color_'.$key] = $color;
        }

        return $this;
    }

    /** Fires on every wheel settle, with the ISO (Y-m-d) value. */
    public function onChange(string $method): static
    {
        $this->componentProps['on_change'] = $method;

        return $this;
    }

    /** Fires when the user taps "Done". */
    public function onDone(string $method): static
    {
        $this->componentProps['on_done'] = $method;

        return $this;
    }

    /** Fires when the user taps "Cancel". */
    public function onCancel(string $method): static
    {
        $this->componentProps['on_cancel'] = $method;

        return $this;
    }

    /**
     * Map Blade attributes onto props. This is a plugin element, so nothing here
     * is read automatically - anything not handled below is silently dropped.
     */
    public function applyAttributes(array $attrs): void
    {
        if (isset($attrs['value'])) {
            $this->value((string) $attrs['value']);
        }

        if (isset($attrs['title'])) {
            $this->title((string) $attrs['title']);
        }

        if (isset($attrs['year-start'])) {
            $this->yearStart((int) $attrs['year-start']);
        }

        if (isset($attrs['year-end'])) {
            $this->yearEnd((int) $attrs['year-end']);
        }

        if (isset($attrs['show-footer'])) {
            $this->showFooter(filter_var($attrs['show-footer'], FILTER_VALIDATE_BOOL));
        }

        if (isset($attrs['colors']) && is_array($attrs['colors'])) {
            $this->colors($attrs['colors']);
        }

        // Event bindings: <native:wheel-date-picker _change="onDateChange" ... />
        if (isset($attrs['_change'])) {
            $this->onChange($attrs['_change']);
        }

        if (isset($attrs['_done'])) {
            $this->onDone($attrs['_done']);
        }

        if (isset($attrs['_cancel'])) {
            $this->onCancel($attrs['_cancel']);
        }
    }

    /**
     * Config-driven fallbacks, merged under whatever the Blade tag/builder set.
     */
    protected function defaults(): array
    {
        $theme = config('wheel-datepicker.theme', []);

        return [
            'value' => now()->format('Y-m-d'),
            'title' => 'Select date',
            'year_start' => (int) config('wheel-datepicker.year_start', 1990),
            'year_end' => (int) config('wheel-datepicker.year_end', now()->year + 20),
            'show_footer' => true,
            'color_bg' => $theme['bg'] ?? '#0e1324',
            'color_card' => $theme['card'] ?? '#151b30',
            'color_border' => $theme['border'] ?? '#232a45',
            'color_text' => $theme['text'] ?? '#e7eaf5',
            'color_muted' => $theme['muted'] ?? '#5b6280',
            'color_muted_2' => $theme['muted_2'] ?? '#3a4160',
            'color_accent' => $theme['accent'] ?? '#7c9bff',
        ];
    }

    /**
     * Final props for the wire node. Callback method names are exchanged for the
     * integer id the renderers send back with wdp events.
     */
    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = array_merge($this->defaults(), $this->componentProps);

        foreach (['on_change', 'on_done', 'on_cancel'] as $callback) {
            if (isset($props[$callback])) {
                $props[$callback] = $registry->register($props[$callback]);
            }
        }

        return $props;
    }
}
