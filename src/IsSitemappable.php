<?php

namespace Vursion\LaravelSitemappable;

trait IsSitemappable
{
	abstract public function shouldBeSitemappable();
	
	protected static function bootIsSitemappable()
	{
		static::saved(function ($model) {
			static::deleteModel($model, false);

			if ($model->shouldBeSitemappable()) {
				static::addModel($model);
			} else {
				static::deleteModel($model, true);
			}
		});

		static::deleted(function ($model) {
			static::deleteModel($model, true);
		});
	}

	protected static function addModel($model)
	{
		$sitemap = Sitemappable::withTrashed()->firstOrCreate([
			'entity_id'   => $model->id,
			'entity_type' => get_class($model),
		]);
		$sitemap->restore();
		$sitemap->urls   = $model->canonical->map(function ($url) {
			return parse_url($url)['path'];
		});
		$sitemap->created_at = ($model->created_at ?? null);
		$sitemap->updated_at = ($model->updated_at ?? null);
		$sitemap->vhosts     = ($model->vhosts ?? null);
		$sitemap->save();
	}

	protected static function deleteModel($model, $forceDelete = false)
	{
		$sitemap = Sitemappable::where('entity_type', get_class($model))
						->where('entity_id', $model->id)
						->withTrashed();

		if ($sitemap) {
			if ($forceDelete) {
				$sitemap->forceDelete();
			} else {
				$sitemap->delete();
			}
		}
	}
}
