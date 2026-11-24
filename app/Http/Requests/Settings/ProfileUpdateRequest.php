<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (config('fortify.lowercase_usernames') && is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->input('section', 'personal')) {
            'location' => ['location' => ['present', 'nullable', 'string', 'max:160']],
            'bio' => ['bio' => ['present', 'nullable', 'string', 'max:2000']],
            'personal' => $this->profileRules($this->user()->id) + ['phone' => ['nullable', 'string', 'max:40']],
            default => ['section' => ['required', 'string', 'in:personal,location,bio']],
        };
    }
}
