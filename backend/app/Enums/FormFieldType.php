<?php

namespace App\Enums;

/**
 * Supported input types for the dynamic event registration form builder.
 */
enum FormFieldType: string
{
    case Text     = 'text';
    case Number   = 'number';
    case Email    = 'email';
    case Select   = 'select';
    case Checkbox = 'checkbox';
    case Radio    = 'radio';
    case Textarea = 'textarea';
    case File     = 'file';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /**
     * Field types whose configuration requires a list of options.
     *
     * @return array<int, string>
     */
    public static function optionTypes(): array
    {
        return [self::Select->value, self::Checkbox->value, self::Radio->value];
    }

    public function requiresOptions(): bool
    {
        return in_array($this->value, self::optionTypes(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Text     => 'Text',
            self::Number   => 'Number',
            self::Email    => 'Email',
            self::Select   => 'Select',
            self::Checkbox => 'Checkbox',
            self::Radio    => 'Radio',
            self::Textarea => 'Textarea',
            self::File     => 'File Upload',
        };
    }
}
