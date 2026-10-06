<?php

namespace App\Http\Requests\App;

use App\Enums\PaymentRule;
use App\Enums\TimelineImpact;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\ScopeItem;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class ChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership of the target is checked by policies in the controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:10000'],
            'scope_reason' => ['nullable', 'string', 'max:5000'],
            'scope_excerpt' => ['nullable', 'string', 'max:5000'],
            'scope_item_ids' => ['sometimes', 'array', 'max:50'],
            'scope_item_ids.*' => ['integer'],
            'price' => ['required', 'string', 'max:20', 'regex:/^\d{1,3}(,?\d{3})*(\.\d+)?$|^\d+(\.\d+)?$/'],
            'currency' => ['required', 'string', Rule::in(Money::SUPPORTED_CURRENCIES)],
            'timeline_type' => ['required', Rule::enum(TimelineImpact::class)],
            'timeline_value' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->input('timeline_type'), ['days', 'weeks', 'date'], true)),
                Rule::when(in_array($this->input('timeline_type'), ['days', 'weeks'], true), ['integer', 'min:1', 'max:365']),
                Rule::when($this->input('timeline_type') === 'date', ['date', 'after_or_equal:today']),
            ],
            'timeline_note' => ['nullable', 'string', 'max:255'],
            'payment_rule' => ['required', Rule::enum(PaymentRule::class)],
            'payment_url' => ['nullable', 'url:https', 'max:2048'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'terms_note' => ['nullable', 'string', 'max:3000'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.regex' => 'Enter a price like 250 or 1,250.50 (no negative amounts).',
            'payment_url.url' => 'The payment link must be a secure https:// URL.',
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    $price = Money::fromDecimal((string) $this->input('price'), (string) $this->input('currency'));
                } catch (InvalidArgumentException $e) {
                    $validator->errors()->add('price', $e->getMessage());

                    return;
                }

                $rule = PaymentRule::from((string) $this->input('payment_rule'));

                if ($rule->requiresPayment() && ! $price->isPositive()) {
                    $validator->errors()->add('price', 'A change that requires payment needs a price greater than zero.');
                }

                $ids = array_map('intval', (array) $this->input('scope_item_ids', []));
                $project = $this->targetProject();

                if ($ids !== [] && $project !== null) {
                    $valid = ScopeItem::query()
                        ->whereIn('id', $ids)
                        ->whereHas('baseline', fn ($q) => $q->where('project_id', $project->id))
                        ->count();

                    if ($valid !== count(array_unique($ids))) {
                        $validator->errors()->add('scope_item_ids', 'Choose scope items from this project only.');
                    }
                }
            },
        ];
    }

    /**
     * Validated data with the price converted to integer minor units.
     *
     * @return array<string, mixed>
     */
    public function revisionData(): array
    {
        $data = $this->validated();
        $data['price_minor'] = Money::fromDecimal((string) $data['price'], (string) $data['currency'])->amount;
        $data['timeline_json'] = [
            'type' => $data['timeline_type'],
            'value' => $data['timeline_type'] === 'none' ? null : ($data['timeline_type'] === 'date' ? (string) $data['timeline_value'] : (int) $data['timeline_value']),
            'note' => $data['timeline_note'] ?? null,
        ];

        if (array_key_exists('recipient_email', $data) && is_string($data['recipient_email'])) {
            $data['recipient_email'] = mb_strtolower($data['recipient_email']);
        }

        return $data;
    }

    protected function targetProject(): ?Project
    {
        $routeProject = $this->route('project');

        if ($routeProject instanceof Project) {
            return $routeProject;
        }

        $changeRequest = $this->route('changeRequest');

        if ($changeRequest instanceof ChangeRequest) {
            return $changeRequest->project;
        }

        $tenant = app(TenantContext::class);

        return $tenant->has()
            ? Project::query()->forWorkspace($tenant->workspace())->find($this->integer('project_id'))
            : null;
    }
}
