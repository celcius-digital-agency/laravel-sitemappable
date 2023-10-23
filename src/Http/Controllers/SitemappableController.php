<?php

namespace Vursion\LaravelSitemappable\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Vursion\LaravelSitemappable\Sitemappable;

class SitemappableController extends Controller
{
	protected $vhost;

	public function __construct()
	{
		$this->vhost = app()->make('vhost');
	}

	public function index()
	{
		$content = Cache::remember('sitemap_' . $this->vhost->slug . '.xml', \DateInterval::createFromDateString(config('sitemappable.cache')), function () {
			$otherRoutes = collect($this->otherRoutes())->map(function ($route) {
				return new Sitemappable([
					'urls' => $route,
				]);
			});

			$sitemappables = Sitemappable::where(function ($query) {
								$query->whereJsonContains('vhosts', $this->vhost->id)
									  ->orWhereNull('vhosts');
							})
							->get()
							->map(function ($sitemappable) {
								$sitemappable['urls'] = collect($sitemappable['urls'])->map(function ($url) {
									return 'https://' . $this->vhost->domain_production . $url;
								});

								return $sitemappable;
							})
							->concat($otherRoutes)
							->filter(function ($sitemappable) {
								return (is_array($sitemappable->urls) && count($sitemappable->urls) > 0);
							});

			return view('sitemappable::sitemap', compact('sitemappables'))->render();
		});

		return response(preg_replace('/>(\s)+</m', '><', $content), '200')->header('Content-Type', 'text/xml');
	}

	protected function otherRoutes()
	{
		return [];
	}
}
