<?php

namespace App\Http\Requests\App;

use App\Enums\ScopeItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.type' => ['required', Rule::enum(ScopeItemType::class)],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.detail' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
