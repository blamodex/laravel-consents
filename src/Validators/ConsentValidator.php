<?php

declare(strict_types=1);

namespace Blamodex\Consent\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates consent payload structure for ConsentService.
 *
 * Expected structure:
 * [
 *   'consents' => [
 *     [
 *       'text' => 'Please send me email updates.',
 *       'source' => 'checkbox',
 *       'slug' => 'email-marketing',
 *       'state' => 'consented'|'revoked'
 *     ]
 *   ]
 * ]
 */
class ConsentValidator
{
    /**
     * Validate the consent payload structure.
     *
     * @param array<string, mixed> $data The payload to validate
     * @return bool True if validation passes
     * @throws ValidationException If validation fails
     */
    public function validate(array $data): bool
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Check if the payload is valid without throwing exceptions.
     *
     * @param array<string, mixed> $data The payload to validate
     * @return bool True if valid, false otherwise
     */
    public function passes(array $data): bool
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        return $validator->passes();
    }

    /**
     * Get validation errors for the payload.
     *
     * @param array<string, mixed> $data The payload to validate
     * @return array<string, array<string>> Validation error messages
     */
    public function errors(array $data): array
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        $validator->fails();

        return $validator->errors()->toArray();
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'consents' => ['required', 'array', 'min:0'],
            'consents.*.text' => ['nullable', 'string', 'max:1000'],
            'consents.*.source' => ['required', 'string', 'max:255'],
            'consents.*.slug' => ['required', 'string', 'max:255'],
            'consents.*.state' => ['required', 'string', 'in:consented,revoked'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'consents.required' => 'The consents field is required.',
            'consents.array' => 'The consents field must be an array.',
            'consents.*.text.string' => 'Each consent text must be a string.',
            'consents.*.text.max' => 'Each consent text must not exceed 1000 characters.',
            'consents.*.source.required' => 'Each consent must have a source.',
            'consents.*.source.string' => 'Each consent source must be a string.',
            'consents.*.source.max' => 'Each consent source must not exceed 255 characters.',
            'consents.*.slug.required' => 'Each consent must have a slug (consent type identifier).',
            'consents.*.slug.string' => 'Each consent slug must be a string.',
            'consents.*.slug.max' => 'Each consent slug must not exceed 255 characters.',
            'consents.*.state.required' => 'Each consent must have a state.',
            'consents.*.state.string' => 'Each consent state must be a string.',
            'consents.*.state.in' => 'Each consent state must be either "consented" or "revoked".',
        ];
    }
}
