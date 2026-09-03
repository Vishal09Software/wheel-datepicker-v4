<?php

namespace NativeUI\WheelDatePicker\Elements;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\UI\Concerns\ResolvesColorValues;

class WheelDatePicker extends Element
{
    use ResolvesColorValues;

    protected string $type = 'wheel_date_picker';

    public const PICKER_STYLES = ['compact', 'inline'];

    public const VISIBLE_ITEM_COUNTS = [3, 5, 7];

    public const REJECTED_SYNC_DIRECTIVES = [
        'blur' => '`native:model.blur`',
        'lazy' => '`native:model.lazy`',
        'debounce' => '`native:model.debounce.Xms`',
    ];

    public const FORMAT_ALIASES = [
        'YYYY-MM-DD' => 'Y-m-d',
        'DD-MM-YYYY' => 'd-m-Y',
        'MM-DD-YYYY' => 'm-d-Y',
        'YYYY/MM/DD' => 'Y/m/d',
        'DD/MM/YYYY' => 'd/m/Y',
        'MM/DD/YYYY' => 'm/d/Y',
        'YYYY.MM.DD' => 'Y.m.d',
        'DD.MM.YYYY' => 'd.m.Y',
    ];

    /** @var array<string, mixed> */
    protected array $pickerProps = [];

    protected ?string $changeCallback = null;

    protected ?string $doneCallback = null;

    protected ?string $cancelCallback = null;

    protected string|DateTimeInterface|null $rawValue = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        if (isset($attrs['value'])) {
            $this->value($attrs['value']);
        }

        if (isset($attrs['label'])) {
            $this->label((string) $attrs['label']);
        }

        if (isset($attrs['title'])) {
            $this->title((string) $attrs['title']);
        }

        if (isset($attrs['placeholder'])) {
            $this->placeholder((string) $attrs['placeholder']);
        }

        if (isset($attrs['format'])) {
            $this->format((string) $attrs['format']);
        }

        $pickerStyle = $attrs['picker-style'] ?? $attrs['pickerStyle'] ?? null;
        if ($pickerStyle !== null) {
            $this->pickerStyle((string) $pickerStyle);
        }

        $confirmLabel = $attrs['confirm-label'] ?? $attrs['confirmLabel'] ?? null;
        if ($confirmLabel !== null) {
            $this->confirmLabel((string) $confirmLabel);
        }

        $cancelLabel = $attrs['cancel-label'] ?? $attrs['cancelLabel'] ?? null;
        if ($cancelLabel !== null) {
            $this->cancelLabel((string) $cancelLabel);
        }

        if (isset($attrs['year-start']) || isset($attrs['yearStart'])) {
            $this->yearStart((int) ($attrs['year-start'] ?? $attrs['yearStart']));
        }

        if (isset($attrs['year-end']) || isset($attrs['yearEnd'])) {
            $this->yearEnd((int) ($attrs['year-end'] ?? $attrs['yearEnd']));
        }

        if (array_key_exists('show-footer', $attrs) || array_key_exists('showFooter', $attrs)) {
            $this->showFooter(filter_var($attrs['show-footer'] ?? $attrs['showFooter'], FILTER_VALIDATE_BOOL));
        }

        if (isset($attrs['colors']) && is_array($attrs['colors'])) {
            $this->colors($attrs['colors']);
        }

        if (isset($attrs['size']) && is_array($attrs['size'])) {
            $this->size($attrs['size']);
        }

        foreach ([
            'row-height' => 'rowHeight',
            'visible-items' => 'visibleItems',
            'wheel-height' => 'wheelHeight',
            'card-padding' => 'cardPadding',
            'corner-radius' => 'cornerRadius',
            'selected-font' => 'selectedFont',
            'muted-font' => 'mutedFont',
        ] as $kebab => $method) {
            $camel = str_replace(' ', '', ucwords(str_replace('-', ' ', $kebab)));
            $camel = lcfirst($camel);

            if (isset($attrs[$kebab]) || isset($attrs[$camel])) {
                $this->{$method}((int) ($attrs[$kebab] ?? $attrs[$camel]));
            }
        }

        if (isset($attrs['_change'])) {
            $this->onChange($attrs['_change']);
        }

        if (isset($attrs['_done'])) {
            $this->onDone($attrs['_done']);
        }

        if (isset($attrs['_cancel'])) {
            $this->onCancel($attrs['_cancel']);
        }

        if (isset($attrs['sync-mode']) || isset($attrs['syncMode'])) {
            $this->syncMode((string) ($attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        $this->applyA11yAttributes($attrs);
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            $directive = self::REJECTED_SYNC_DIRECTIVES[$mode] ?? "`native:model.{$mode}`";

            throw new InvalidArgumentException(
                "WheelDatePicker commits on confirmation, so the {$directive} sync mode has no effect. "
                .'Use plain `native:model` (or `native:model.live`).'
            );
        }

        return $this;
    }

    public function value(string|DateTimeInterface|null $value): static
    {
        $this->rawValue = $value;

        return $this;
    }

    public function label(string $text): static
    {
        $this->pickerProps['label'] = $text;

        return $this;
    }

    public function title(string $title): static
    {
        $this->pickerProps['title'] = $title;

        return $this;
    }

    public function placeholder(string $text): static
    {
        $this->pickerProps['placeholder'] = $text;

        return $this;
    }

    public function confirmLabel(string $text): static
    {
        $this->pickerProps['confirm_label'] = $text;

        return $this;
    }

    public function cancelLabel(string $text): static
    {
        $this->pickerProps['cancel_label'] = $text;

        return $this;
    }

    public function pickerStyle(string $style): static
    {
        $this->pickerProps['picker_style'] = $this->oneOf($style, self::PICKER_STYLES, 'picker-style');

        return $this;
    }

    public function format(string $format): static
    {
        $this->pickerProps['format'] = $this->phpFormat($format);

        return $this;
    }

    public function yearStart(int $year): static
    {
        $this->pickerProps['year_start'] = $year;

        return $this;
    }

    public function yearEnd(int $year): static
    {
        $this->pickerProps['year_end'] = $year;

        return $this;
    }

    public function showFooter(bool $show = true): static
    {
        $this->pickerProps['show_footer'] = $show;

        return $this;
    }

    /**
     * @param  array<string, int|string|null>  $size
     */
    public function size(array $size): static
    {
        $map = [
            'row_height' => 'rowHeight',
            'row-height' => 'rowHeight',
            'visible_items' => 'visibleItems',
            'visible-items' => 'visibleItems',
            'wheel_height' => 'wheelHeight',
            'wheel-height' => 'wheelHeight',
            'card_padding' => 'cardPadding',
            'card-padding' => 'cardPadding',
            'corner_radius' => 'cornerRadius',
            'corner-radius' => 'cornerRadius',
            'selected_font' => 'selectedFont',
            'selected-font' => 'selectedFont',
            'muted_font' => 'mutedFont',
            'muted-font' => 'mutedFont',
        ];

        foreach ($size as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $method = $map[$key] ?? null;

            if ($method !== null) {
                $this->{$method}((int) $value);
            }
        }

        return $this;
    }

    public function rowHeight(int $dp): static
    {
        $this->pickerProps['row_height'] = max(24, min(80, $dp));

        return $this;
    }

    public function visibleItems(int $count): static
    {
        $this->pickerProps['visible_items'] = $this->clampVisibleItems($count);

        return $this;
    }

    public function wheelHeight(int $dp): static
    {
        $this->pickerProps['wheel_height'] = max(0, $dp);

        return $this;
    }

    public function cardPadding(int $dp): static
    {
        $this->pickerProps['card_padding'] = max(0, min(48, $dp));

        return $this;
    }

    public function cornerRadius(int $dp): static
    {
        $this->pickerProps['corner_radius'] = max(0, min(48, $dp));

        return $this;
    }

    public function selectedFont(int $sp): static
    {
        $this->pickerProps['selected_font'] = max(12, min(32, $sp));

        return $this;
    }

    public function mutedFont(int $sp): static
    {
        $this->pickerProps['muted_font'] = max(10, min(24, $sp));

        return $this;
    }

    /**
     * @param  array<string, string>  $colors
     */
    public function colors(array $colors): static
    {
        foreach ($colors as $key => $color) {
            if (! is_string($color) || $color === '') {
                continue;
            }

            $this->pickerProps['color_'.$key] = $this->resolveColorValue($color);
        }

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    public function onDone(string $method): static
    {
        $this->doneCallback = $method;

        return $this;
    }

    public function onCancel(string $method): static
    {
        $this->cancelCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = $this->pickerProps;
        $configTheme = config('wheel-datepicker.theme', []);
        $nativeUiColors = $this->nativeUiPickerColors();

        $props['year_start'] ??= (int) config('wheel-datepicker.year_start', 1990);
        $props['year_end'] ??= (int) (config('wheel-datepicker.year_end') ?: now()->year + 20);

        // Renderers (Android especially) assume year_start <= year_end and will
        // crash or render inconsistently otherwise. Normalize once here so both
        // native platforms always receive a valid, non-empty, ascending range.
        if ($props['year_start'] > $props['year_end']) {
            [$props['year_start'], $props['year_end']] = [$props['year_end'], $props['year_start']];
        }

        $props['picker_style'] ??= 'compact';
        $props['format'] ??= $this->phpFormat((string) config('wheel-datepicker.format', 'Y-m-d'));
        $props['pattern'] = $this->nativePattern($props['format']);
        $props['show_footer'] ??= ($props['picker_style'] === 'inline');
        $props['title'] ??= 'Select date';
        $props['confirm_label'] ??= 'Done';
        $props['cancel_label'] ??= 'Cancel';
        $props['placeholder'] ??= $props['format'];

        $configSize = config('wheel-datepicker.size', []);
        $rowHeight = (int) ($props['row_height'] ?? $configSize['row_height'] ?? 44);
        $visibleItems = $this->clampVisibleItems((int) ($props['visible_items'] ?? $configSize['visible_items'] ?? 5));
        $wheelHeight = (int) ($props['wheel_height'] ?? $configSize['wheel_height'] ?? 0);

        $props['row_height'] = max(24, min(80, $rowHeight));
        $props['visible_items'] = $visibleItems;
        $props['wheel_height'] = $wheelHeight > 0 ? $wheelHeight : $props['row_height'] * $props['visible_items'];
        $props['card_padding'] = max(0, min(48, (int) ($props['card_padding'] ?? $configSize['card_padding'] ?? 16)));
        $props['corner_radius'] = max(0, min(48, (int) ($props['corner_radius'] ?? $configSize['corner_radius'] ?? 20)));
        $props['selected_font'] = max(12, min(32, (int) ($props['selected_font'] ?? $configSize['selected_font'] ?? 20)));
        $props['muted_font'] = max(10, min(24, (int) ($props['muted_font'] ?? $configSize['muted_font'] ?? 15)));

        foreach (['bg', 'card', 'border', 'text', 'muted', 'muted_2', 'accent'] as $key) {
            $prop = 'color_'.$key;
            $fromConfig = $configTheme[$key] ?? null;
            $fromNativeUi = $nativeUiColors[$key] ?? null;

            if (isset($props[$prop])) {
                continue;
            }

            $color = is_string($fromConfig) && $fromConfig !== ''
                ? $fromConfig
                : $fromNativeUi;

            if (is_string($color) && $color !== '') {
                $props[$prop] = $this->resolveColorValue($color);
            }
        }

        $normalized = $this->normalize($this->rawValue, $props['format']);

        if ($normalized !== null) {
            $props['value'] = $normalized;
        }

        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

        if ($this->doneCallback !== null) {
            $props['on_done'] = $registry->register($this->doneCallback);
        }

        if ($this->cancelCallback !== null) {
            $props['on_cancel'] = $registry->register($this->cancelCallback);
        }

        return $props;
    }

    private function phpFormat(string $format): string
    {
        $trimmed = trim($format);

        if ($trimmed === '') {
            return 'Y-m-d';
        }

        return self::FORMAT_ALIASES[strtoupper($trimmed)] ?? $trimmed;
    }

    private function nativePattern(string $phpFormat): string
    {
        return strtr($phpFormat, [
            'Y' => 'yyyy',
            'm' => 'MM',
            'd' => 'dd',
        ]);
    }

    private function normalize(string|DateTimeInterface|null $raw, string $phpFormat): ?string
    {
        if ($raw === null) {
            return null;
        }

        if ($raw instanceof DateTimeInterface) {
            return $raw->format($phpFormat);
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            return '';
        }

        foreach ($this->parseFormats($phpFormat) as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!'.$format, $trimmed, new DateTimeZone('UTC'));

            if ($parsed instanceof DateTimeImmutable && $parsed->format($format) === $trimmed) {
                return $parsed->format($phpFormat);
            }
        }

        try {
            return (new DateTimeImmutable($trimmed, new DateTimeZone('UTC')))->format($phpFormat);
        } catch (Exception) {
            throw new InvalidArgumentException(
                "WheelDatePicker `value` expects a date matching [{$phpFormat}], got [{$trimmed}]."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function parseFormats(string $phpFormat): array
    {
        return array_values(array_unique([
            $phpFormat,
            'Y-m-d',
            'd-m-Y',
            'm-d-Y',
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function nativeUiPickerColors(): array
    {
        $tokens = config('native-ui.theme.light', []);

        if (! is_array($tokens)) {
            $tokens = [];
        }

        return array_filter([
            'bg' => $tokens['background'] ?? '#FFFFFF',
            'card' => $tokens['surface'] ?? '#FFFFFF',
            'border' => $tokens['outline'] ?? '#79747E',
            'text' => $tokens['on-surface'] ?? '#1C1B1F',
            'muted' => $tokens['on-surface-variant'] ?? '#49454F',
            'muted_2' => $tokens['surface-variant'] ?? '#E7E0EC',
            'accent' => $tokens['primary'] ?? '#6750A4',
        ], fn (mixed $color): bool => is_string($color) && $color !== '');
    }

    private function clampVisibleItems(int $count): int
    {
        if (in_array($count, self::VISIBLE_ITEM_COUNTS, true)) {
            return $count;
        }

        if ($count <= 3) {
            return 3;
        }

        if ($count >= 7) {
            return 7;
        }

        return 5;
    }

    /** @param  list<string>  $allowed */
    private function oneOf(string $value, array $allowed, string $prop): string
    {
        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(
                "WheelDatePicker `{$prop}` must be one of [".implode(', ', $allowed)."], got [{$value}]."
            );
        }

        return $value;
    }
}
