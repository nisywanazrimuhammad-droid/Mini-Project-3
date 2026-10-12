<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, string $label): self
    {
        $value = trim((string)($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field][] = "{$label} wajib diisi.";
        }
        return $this;
    }

    public function minLength(string $field, string $label, int $min): self
    {
        $value = trim((string)($this->data[$field] ?? ''));
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field][] = "{$label} minimal {$min} karakter.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "{$label} harus berupa angka numerik.";
        }
        return $this;
    }

    public function positiveNumber(string $field, string $label, bool $allowZero = false): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '') {
            if (!is_numeric($value)) {
                $this->errors[$field][] = "{$label} harus berupa angka.";
            } else {
                $num = (float)$value;
                if ($allowZero && $num < 0) {
                    $this->errors[$field][] = "{$label} tidak boleh bernilai negatif.";
                } elseif (!$allowZero && $num <= 0) {
                    $this->errors[$field][] = "{$label} harus lebih besar dari 0.";
                }
            }
        }
        return $this;
    }

    public function inArray(string $field, string $label, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "Pilihan {$label} tidak valid.";
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = trim((string)($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Format {$label} tidak valid.";
        }
        return $this;
    }

    public function custom(bool $condition, string $field, string $message): self
    {
        if (!$condition) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
