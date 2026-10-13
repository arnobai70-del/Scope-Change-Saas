<?php

namespace App\Services;

use App\Http\Controllers\Marketing\MarketingController;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SEO and analytics settings managed from the admin panel: page meta tags,
 * Google Analytics, robots.txt and sitemap.xml. Each section is one
 * site_settings row, cached until the next save.
 */
class SeoService
{
    public const SECTIONS = ['meta', 'analytics', 'robots', 'sitemap'];

    private const CACHE_KEY = 'site_settings.seo';

    private const STATIC_PATHS = [
        '/', '/features', '/how-it-works', '/pricing', '/templates/change-request',
        '/tools/change-request-generator', '/tools/scope-creep-calculator', '/examples/client-approval',
        '/blog', '/docs', '/security', '/contact', '/legal/terms', '/legal/privacy', '/legal/dpa', '/legal/refund', '/subprocessors',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function defaults(): array
    {
        return [
            'meta' => ['default_description' => '', 'og_image' => '', 'google_verification' => '', 'discourage_indexing' => false, 'pages' => []],
            'analytics' => ['ga_id' => ''],
            'robots' => ['custom' => null],
            'sitemap' => ['include_segments' => true, 'include_posts' => true, 'extra_urls' => [], 'exclude' => []],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        try {
            /** @var array<string, array<string, mixed>> $stored */
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::query()
                ->whereIn('key', array_map(fn ($s) => "seo.{$s}", self::SECTIONS))
                ->get()
                ->mapWithKeys(fn (SiteSetting $row) => [substr($row->key, 4) => $row->value ?? []])
                ->all());
        } catch (QueryException) {
            // The table does not exist until migrations run; fall back to defaults.
            $stored = [];
        }

        $settings = [];
        foreach ($this->defaults() as $section => $defaults) {
            $settings[$section] = array_merge($defaults, $stored[$section] ?? []);
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function section(string $section): array
    {
        return $this->all()[$section];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function save(string $section, array $value): SiteSetting
    {
        $row = SiteSetting::query()->updateOrCreate(['key' => "seo.{$section}"], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);

        return $row;
    }

    /**
     * Every public page that can carry custom meta tags or appear in the sitemap.
     *
     * @return list<string>
     */
    public function pagePaths(): array
    {
        return array_merge(
            self::STATIC_PATHS,
            array_map(fn ($s) => "/for/{$s}", array_keys(MarketingController::SEGMENTS)),
            array_map(fn ($s) => "/blog/{$s}", array_keys(MarketingController::POSTS)),
        );
    }

    /**
     * @return Collection<int, string>
     */
    public function sitemapUrls(): Collection
    {
        $config = $this->section('sitemap');
        /** @var list<string> $exclude */
        $exclude = $config['exclude'];

        $paths = collect($this->pagePaths())
            ->reject(fn ($p) => ! $config['include_segments'] && str_starts_with($p, '/for/'))
            ->reject(fn ($p) => ! $config['include_posts'] && str_starts_with($p, '/blog/'))
            ->reject(fn ($p) => in_array($p, $exclude, true))
            ->map(fn ($p) => url($p));

        /** @var list<string> $extra */
        $extra = $config['extra_urls'];

        return $paths->merge(array_map(fn ($u) => str_starts_with($u, '/') ? url($u) : $u, $extra))->unique()->values();
    }

    public function defaultRobots(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /app/',
            'Disallow: /c/',
            'Disallow: /admin/',
            'Disallow: /settings/',
            'Sitemap: '.url('/sitemap.xml'),
        ])."\n";
    }

    public function robots(): string
    {
        if ($this->section('meta')['discourage_indexing']) {
            return "User-agent: *\nDisallow: /\n";
        }

        $custom = $this->section('robots')['custom'];

        return filled($custom) ? rtrim((string) $custom)."\n" : $this->defaultRobots();
    }

    /**
     * View composer for the public layout: applies page overrides, the
     * default description, verification and analytics tags.
     */
    public function compose(View $view): void
    {
        $settings = $this->all();
        $meta = $settings['meta'];
        $data = $view->getData();
        $path = request()->path() === '/' ? '/' : '/'.request()->path();
        /** @var array<string, array{title?: string|null, description?: string|null}> $pages */
        $pages = $meta['pages'];
        $override = $pages[$path] ?? [];
        // Private pages (client approval links, receipts) are never tracked.
        $private = (bool) ($data['noindex'] ?? false);

        if (filled($override['title'] ?? null)) {
            $view->with('title', $override['title']);
        }

        if (filled($override['description'] ?? null)) {
            $view->with('description', $override['description']);
        } elseif (! isset($data['description']) && filled($meta['default_description'])) {
            $view->with('description', $meta['default_description']);
        }

        if ($meta['discourage_indexing']) {
            $view->with('noindex', true);
        }

        $view->with('seo', [
            'og_image' => $meta['og_image'],
            'google_verification' => $meta['google_verification'],
            'ga_id' => $private ? '' : $settings['analytics']['ga_id'],
        ]);
    }
}
