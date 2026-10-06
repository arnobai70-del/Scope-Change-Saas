<?php

namespace App\Http\Requests\App;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkspaceSettingsRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'currency' => ['required', Rule::in(Money::SUPPORTED_CURRENCIES)],
            'timezone' => ['required', 'timezone:all'],
            'service_type' => ['nullable', 'string', 'max:60'],
            'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'default_expiry_days' => ['required', 'integer', 'min:1', 'max:90'],
            'reminders_enabled' => ['required', 'boolean'],
            'default_payment_url' => ['nullable', 'url:https', 'max:2048'],
            'default_payment_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
