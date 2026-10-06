<?php

use App\Http\Controllers\Marketing\MarketingController as Marketing;
use Illuminate\Support\Facades\Route;

/*
| Public marketing site and free tools (server-rendered for SEO).
*/
Route::get('/', [Marketing::class, 'home'])->name('home');
Route::get('/pricing', [Marketing::class, 'pricing'])->name('pricing');
Route::get('/for/{segment}', [Marketing::class, 'segment'])->name('marketing.segment');
Route::get('/legal/{page}', [Marketing::class, 'legal'])->name('legal');
Route::get('/blog', [Marketing::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [Marketing::class, 'post'])->name('blog.post');
Route::get('/tools/change-request-generator', [Marketing::class, 'generator'])->middleware('throttle:60,1')->name('tools.generator');
Route::get('/tools/scope-creep-calculator', [Marketing::class, 'calculator'])->middleware('throttle:60,1')->name('tools.calculator');
Route::get('/contact', [Marketing::class, 'contact'])->name('contact');
Route::post('/contact', [Marketing::class, 'submitContact'])->middleware('throttle:5,10')->name('contact.submit');
Route::get('/sitemap.xml', [Marketing::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Marketing::class, 'robots'])->name('robots');

foreach (['features', 'how-it-works', 'security', 'docs', 'status', 'subprocessors', 'templates/change-request', 'examples/client-approval'] as $page) {
    Route::get('/'.$page, [Marketing::class, 'page'])->defaults('page', $page)->name('marketing.'.str_replace('/', '.', $page));
}

require __DIR__.'/app.php';
require __DIR__.'/client.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
