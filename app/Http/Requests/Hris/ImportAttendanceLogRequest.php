<?php

namespace App\Http\Requests\Hris;

use Illuminate\Foundation\Http\FormRequest;

class ImportAttendanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-hris-resource', 'attendance');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:dat,txt', 'max:5120'],
            'confirm' => ['sometimes', 'boolean'],
        ];
    }
}
