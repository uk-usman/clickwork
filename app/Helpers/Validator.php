<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Small server-side validator. Client-side validation is never trusted alone.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array<string,string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function asString(string $field): string
    {
        $value = $this->value($field);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        if ($this->asString($field) === '') {
            $this->addError($field, $label . ' is required.');
        }

        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $value = $this->asString($field);
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, $label . ' must be a valid email address.');
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->addError($field, sprintf('%s must be at least %d characters.', $label, $min));
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if (mb_strlen($value) > $max) {
            $this->addError($field, sprintf('%s must not exceed %d characters.', $label, $max));
        }

        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && !is_numeric($value)) {
            $this->addError($field, $label . ' must be a number.');
        }

        return $this;
    }

    public function integer(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $label . ' must be a whole number.');
        }

        return $this;
    }

    public function min(string $field, float $min, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && is_numeric($value) && (float) $value < $min) {
            $this->addError($field, sprintf('%s must be at least %s.', $label, rtrim(rtrim(number_format($min, 2, '.', ''), '0'), '.')));
        }

        return $this;
    }

    public function max(string $field, float $max, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && is_numeric($value) && (float) $value > $max) {
            $this->addError($field, sprintf('%s must not exceed %s.', $label, rtrim(rtrim(number_format($max, 2, '.', ''), '0'), '.')));
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value !== '' && !in_array($value, array_map('strval', $allowed), true)) {
            $this->addError($field, $label . ' contains an invalid selection.');
        }

        return $this;
    }

    public function date(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value === '') {
            return $this;
        }
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            $this->addError($field, $label . ' must be a valid date (YYYY-MM-DD).');
        }

        return $this;
    }

    public function after(string $field, string $otherField, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $a = $this->asString($field);
        $b = $this->asString($otherField);
        if ($a !== '' && $b !== '' && $a < $b) {
            $this->addError($field, $label . ' must not be earlier than ' . humanize($otherField) . '.');
        }

        return $this;
    }

    public function url(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        $value = $this->asString($field);
        if ($value === '') {
            return $this;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->addError($field, $label . ' must be a valid http(s) URL.');
        }

        return $this;
    }

    public function confirmed(string $field, string $label = ''): self
    {
        $label = $label !== '' ? $label : humanize($field);
        if ($this->asString($field) !== $this->asString($field . '_confirmation')) {
            $this->addError($field . '_confirmation', $label . ' confirmation does not match.');
        }

        return $this;
    }

    public function password(string $field, int $min = 8, string $label = 'Password'): self
    {
        $value = $this->asString($field);
        if ($value === '') {
            return $this;
        }
        if (mb_strlen($value) < $min) {
            $this->addError($field, sprintf('%s must be at least %d characters.', $label, $min));
        } elseif (preg_match('/[A-Za-z]/', $value) !== 1 || preg_match('/[0-9]/', $value) !== 1) {
            $this->addError($field, $label . ' must contain at least one letter and one number.');
        }

        return $this;
    }

    /**
     * Custom rule callback. Return an error message or null.
     *
     * @param callable(mixed):?string $callback
     */
    public function rule(string $field, callable $callback): self
    {
        $message = $callback($this->value($field));
        if (is_string($message) && $message !== '') {
            $this->addError($field, $message);
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->addError($field, $message);

        return $this;
    }
}
