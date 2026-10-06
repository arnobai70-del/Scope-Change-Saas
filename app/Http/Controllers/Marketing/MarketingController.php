<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\PlanService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * Server-rendered, crawlable marketing pages and free tools.
 */
class MarketingController extends Controller
{
    public const SEGMENTS = [
        'freelancers' => [
            'title' => 'Change requests for freelancers',
            'headline' => 'Stop doing "one small extra" for free.',
            'intro' => 'You agreed a scope. Then the client asks for a few more pages, another revision round, a quick integration. Turn each of those into a priced change your client approves before you start.',
            'examples' => ['Add a blog section to a 5-page brochure site', 'A third round of logo revisions', 'Exporting designs for social media sizes'],
        ],
        'agencies' => [
            'title' => 'Scope change approvals for agencies',
            'headline' => 'Turn scope creep into approved change revenue before your team starts work.',
            'intro' => 'Give account managers one calm, consistent process for out-of-scope requests, with a shared audit trail and reporting on the revenue your team protected.',
            'examples' => ['Extra landing page variants for a campaign', 'New integration requested mid-sprint', 'Additional language versions of a site'],
        ],
        'consultants' => [
            'title' => 'Change orders for consultants',
            'headline' => 'Document the change, price the impact, get a clean approval trail.',
            'intro' => 'Implementation partners and consultants live on fixed-scope engagements. Record each change with its price and timeline impact, and keep the evidence for the final invoice.',
            'examples' => ['Extra workshop day', 'Migrating an additional data source', 'Custom report outside the agreed set'],
        ],
    ];

    public const POSTS = [
        'what-is-scope-creep' => [
            'title' => 'What is scope creep? Examples and how to stop it',
            'description' => 'Scope creep is the slow growth of a project beyond what was agreed. Here is how to spot it early and handle it without damaging the relationship.',
            'body' => [
                'Scope creep is any work that grows a project beyond the deliverables, revisions and assumptions you and the client agreed at the start. It rarely arrives as a big request. It arrives as "could you just…".',
                'Common examples: an extra page on a website, another round of design revisions, a new integration, content you were supposed to receive but now have to write, or a deadline that moves while the scope stays the same.',
                'The fix is not to say no. It is to make every extra request visible and priced: compare it to the agreed scope, state the price and timeline impact, and ask for approval before you start.',
                'A short written change request does that in a way clients find reasonable. It protects the relationship as much as your revenue, because nobody is surprised by the final invoice.',
            ],
        ],
        'how-to-charge-for-extra-work' => [
            'title' => 'How to charge a client for extra work (with an email template)',
            'description' => 'A calm, professional way to tell a client that a request is outside the agreed scope, with a copy-ready template.',
            'body' => [
                'Start by thanking the client for the request and confirming you understood it. Then reference the agreed scope in one sentence, so the conversation is about the document, not about either person.',
                'State the price and the timeline impact plainly. Avoid apologising for charging: you are offering a clear option, and the client stays in control of the decision.',
                'Finish with one clear action: approve, decline or ask a question. A link to a change request page makes this a one-click decision and leaves a timestamped record.',
                'Template: "Thanks for the idea about X. It sits outside the scope we agreed (which covered Y). I can add it for [price], which moves delivery by [N days]. If you\'d like to go ahead, approve it here: [link]."',
            ],
        ],
        'change-request-template' => [
            'title' => 'Freelance change request template: what to include',
            'description' => 'The seven fields every change request needs so it can be approved quickly and used as evidence later.',
            'body' => [
                'A good change request is short. It needs: a reference number, the requested change in plain language, why it is outside the agreed scope, the price and currency, the timeline impact, the payment condition, and an approval with a name and timestamp.',
                'Link the change to the original scope item it extends. That single line removes most disputes later, because both sides can see what was and was not included.',
                'Keep approved changes immutable. If anything changes after approval, create a new revision rather than editing the old one, so the history stays trustworthy.',
            ],
        ],
    ];

    public function home(PlanService $plans): View
    {
        return view('marketing.home', ['plans' => $this->planCards($plans)]);
    }

    public function page(string $page): View
    {
        $views = [
            'features' => 'marketing.features',
            'how-it-works' => 'marketing.how-it-works',
            'security' => 'marketing.security',
            'docs' => 'marketing.docs',
            'status' => 'marketing.status',
            'subprocessors' => 'marketing.subprocessors',
            'templates/change-request' => 'marketing.template',
            'examples/client-approval' => 'marketing.example',
        ];

        abort_unless(isset($views[$page]), 404);

        return view($views[$page]);
    }

    public function pricing(PlanService $plans): View
    {
        return view('marketing.pricing', ['plans' => $this->planCards($plans)]);
    }

    public function segment(string $segment): View
    {
        abort_unless(isset(self::SEGMENTS[$segment]), 404);

        return view('marketing.segment', ['segment' => self::SEGMENTS[$segment], 'key' => $segment]);
    }

    public function legal(string $page): View
    {
        abort_unless(in_array($page, ['terms', 'privacy', 'dpa', 'refund'], true), 404);

        return view('marketing.legal.'.$page);
    }

    public function blog(): View
    {
        return view('marketing.blog', ['posts' => self::POSTS]);
    }

    public function post(string $slug): View
    {
        abort_unless(isset(self::POSTS[$slug]), 404);

        return view('marketing.post', ['post' => self::POSTS[$slug], 'slug' => $slug]);
    }

    /**
     * Free change request generator. Works with plain GET parameters so the
     * output is shareable and the page needs no JavaScript.
     */
    public function generator(Request $request, AnalyticsService $analytics): View
    {
        $input = $request->validate([
            'client' => ['nullable', 'string', 'max:120'],
            'project' => ['nullable', 'string', 'max:160'],
            'change' => ['nullable', 'string', 'max:2000'],
            'original' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'in:'.implode(',', Money::SUPPORTED_CURRENCIES)],
            'days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment' => ['nullable', 'in:none,before_start,before_handoff'],
        ]);

        $output = null;

        if (filled($input['change'] ?? null)) {
            $currency = $input['currency'] ?? 'USD';
            try {
                $price = filled($input['price'] ?? null) ? Money::fromDecimal((string) $input['price'], $currency)->format() : null;
            } catch (InvalidArgumentException) {
                $price = null;
            }

            $output = view('marketing.partials.generated-request', [
                'client' => $input['client'] ?: 'there',
                'project' => $input['project'] ?: 'the project',
                'change' => $input['change'],
                'original' => $input['original'] ?? null,
                'price' => $price,
                'days' => (int) ($input['days'] ?? 0),
                'payment' => $input['payment'] ?? 'none',
            ])->render();

            $analytics->track('free_tool_used', null, null, ['tool' => 'change_request_generator']);
        }

        return view('marketing.tools.generator', ['input' => $input, 'output' => $output, 'currencies' => Money::SUPPORTED_CURRENCIES]);
    }

    public function calculator(Request $request, AnalyticsService $analytics): View
    {
        $input = $request->validate([
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'requests' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'projects' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $result = null;

        if (isset($input['rate'], $input['hours'], $input['requests'], $input['projects'])) {
            $perProject = (float) $input['rate'] * (float) $input['hours'] * (int) $input['requests'];
            $result = [
                'per_project' => round($perProject, 2),
                'per_year' => round($perProject * (int) $input['projects'], 2),
                'hours_per_year' => round((float) $input['hours'] * (int) $input['requests'] * (int) $input['projects'], 1),
            ];

            $analytics->track('free_tool_used', null, null, ['tool' => 'scope_creep_calculator']);
        }

        return view('marketing.tools.calculator', ['input' => $input, 'result' => $result]);
    }

    public function contact(): View
    {
        return view('marketing.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'website' => ['prohibited'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        SupportTicket::query()->create([
            'user_id' => $user?->id,
            'workspace_id' => $user?->current_workspace_id,
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'subject' => $data['subject'],
            'body' => strip_tags($data['body']),
            'priority' => 'normal',
        ]);

        return back()->with('status', 'Thanks, we received your message and will reply by email.');
    }

    public function sitemap(): Response
    {
        $urls = collect([
            '/', '/features', '/how-it-works', '/pricing', '/templates/change-request',
            '/tools/change-request-generator', '/tools/scope-creep-calculator', '/examples/client-approval',
            '/blog', '/docs', '/security', '/contact', '/legal/terms', '/legal/privacy', '/legal/dpa', '/legal/refund', '/subprocessors',
        ])
            ->merge(collect(array_keys(self::SEGMENTS))->map(fn ($s) => "/for/{$s}"))
            ->merge(collect(array_keys(self::POSTS))->map(fn ($s) => "/blog/{$s}"))
            ->map(fn ($path) => url($path));

        return response()->view('marketing.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /app/',
            'Disallow: /c/',
            'Disallow: /admin/',
            'Disallow: /settings/',
            'Sitemap: '.url('/sitemap.xml'),
        ])."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function planCards(PlanService $plans): array
    {
        return array_values(collect($plans->all())->map(fn ($plan, $key) => [
            'key' => $key,
            'name' => $plan['name'],
            'description' => $plan['description'],
            'monthly' => (new Money((int) $plan['monthly_minor'], 'USD'))->format(),
            'yearly' => (new Money((int) $plan['yearly_minor'], 'USD'))->format(),
            'highlights' => $plan['highlights'],
        ])->all());
    }
}
