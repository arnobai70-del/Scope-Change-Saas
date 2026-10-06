<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\ClientRequest;
use App\Models\Client;
use App\Models\ClientContact;
use App\Services\AnalyticsService;
use App\Services\AuditTrailService;
use App\Support\Presenters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
    ) {}

    public function index(Request $request): Response
    {
        $showArchived = $request->boolean('archived');
        $search = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->forWorkspace($this->workspace())
            ->when(! $showArchived, fn ($q) => $q->whereNull('archived_at'))
            ->when($showArchived, fn ($q) => $q->whereNotNull('archived_at'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->withCount('projects')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Client $client) => Presenters::client($client) + ['projects_count' => $client->getAttribute('projects_count')]);

        return Inertia::render('clients/Index', [
            'clients' => $clients,
            'filters' => ['archived' => $showArchived, 'q' => $search],
            'openCreate' => $request->boolean('new'),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $workspace = $this->workspace();

        $client = DB::transaction(function () use ($request, $workspace) {
            $client = new Client($request->validated());
            $client->workspace_id = $workspace->id;
            $client->save();

            if ($client->email) {
                $client->contacts()->create(['name' => $client->name, 'email' => $client->email, 'is_primary' => true]);
            }

            $this->audit->record($client, 'client.created', ActorType::User, $this->user());

            return $client;
        });

        $this->analytics->track('client_created', $workspace->id, $this->user()->id);
        $this->toast('Client added.');

        return to_route('clients.show', $client);
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $client->load(['contacts', 'projects' => fn ($q) => $q->latest()]);

        return Inertia::render('clients/Show', [
            'client' => Presenters::client($client),
            'contacts' => $client->contacts->map(fn (ClientContact $c) => $c->only(['id', 'name', 'email', 'role', 'is_primary'])),
            'projects' => $client->projects->map(fn ($p) => Presenters::project($p)),
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->fill($request->validated())->save();
        $this->audit->record($client, 'client.updated', ActorType::User, $this->user());
        $this->toast('Client updated.');

        return back();
    }

    public function archive(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->forceFill(['archived_at' => $client->archived_at ? null : Carbon::now()])->save();
        $this->audit->record($client, $client->archived_at ? 'client.archived' : 'client.restored', ActorType::User, $this->user());
        $this->toast($client->archived_at ? 'Client archived.' : 'Client restored.');

        return back();
    }

    public function storeContact(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'max:80'],
            'is_primary' => ['boolean'],
        ]);

        DB::transaction(function () use ($client, $data) {
            if ($data['is_primary'] ?? false) {
                $client->contacts()->update(['is_primary' => false]);
            }

            $client->contacts()->create($data + ['is_primary' => $client->contacts()->doesntExist()]);
        });

        $this->toast('Contact added.');

        return back();
    }

    public function destroyContact(Client $client, ClientContact $contact): RedirectResponse
    {
        $this->authorize('update', $client);
        abort_unless($contact->client_id === $client->id, 404);

        $contact->delete();
        $this->toast('Contact removed.');

        return back();
    }
}
