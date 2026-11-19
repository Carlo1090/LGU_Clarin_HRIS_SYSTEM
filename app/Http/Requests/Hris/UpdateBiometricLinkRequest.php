<?php

namespace App\Http\Requests\Hris;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiometricLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-hris-resource', 'attendance');
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
        ];
    }
}
