<?php

namespace App\Http\Requests\App;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class ProjectRequest extends FormRequest
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
            'client_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40'],
            'base_amount' => ['nullable', 'string', 'max:20', 'regex:/^[\d,]+(\.\d+)?$/'],
            'currency' => ['required', Rule::in(Money::SUPPORTED_CURRENCIES)],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)->except([ProjectStatus::Archived])],
            'revision_allowance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenant = app(TenantContext::class);

                if (! $tenant->has() || ! Client::query()->forWorkspace($tenant->workspace())->whereNull('archived_at')->whereKey($this->integer('client_id'))->exists()) {
                    $validator->errors()->add('client_id', 'Choose one of your active clients.');
                }

                if (filled($this->input('base_amount')) && Money::isSupportedCurrency((string) $this->input('currency'))) {
                    try {
                        Money::fromDecimal((string) $this->input('base_amount'), (string) $this->input('currency'));
                    } catch (InvalidArgumentException $e) {
                        $validator->errors()->add('base_amount', $e->getMessage());
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectData(): array
    {
        $data = $this->validated();
        $data['base_amount_minor'] = filled($data['base_amount'] ?? null)
            ? Money::fromDecimal((string) $data['base_amount'], (string) $data['currency'])->amount
            : 0;
        unset($data['base_amount']);

        return $data;
    }
}
