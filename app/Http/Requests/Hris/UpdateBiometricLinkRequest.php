<?php

namespace App\Http\Requests\Hris;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiometricLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role?->name, ['HR Officer', 'HRMO Head'], true);
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('biometric_id');
        if (is_string($id) && ctype_digit($id)) {
            $this->merge(['biometric_id' => ltrim($id, '0') ?: '0']);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'biometric_id' => ['present', 'nullable', 'integer', 'between:1,4294967295'],
            'verify_only' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'effective_to' => ['nullable', 'date_format:Y-m-d\TH:i', ...($this->filled('effective_from') ? ['after:effective_from'] : [])],
            'previous_effective_to' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];
    }
}
