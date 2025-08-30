<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Arr;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        define('APP_ACTIVE', 1);
        define('APP_INACTIVE', 0);

        define('APP_TRUE', true);
        define('APP_FALSE', false);
        
        Builder::macro(
            'whereLike',
            function($attributes, string $search) {
                /** @var \Illuminate\Database\Eloquent\Builder $this */
                $attributes = Arr::wrap($attributes);
                $search = trim($search);
                if ($search === '') return $this;
            
                // PGSQL esetén ILIKE, egyébként LIKE
                $like = $this->getQuery()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            
                // Több szóra bontjuk: minden szónak illeszkednie kell (AND),
                // de bármelyik mezőben illeszkedhet (OR).
                $terms = preg_split('/\s+/', $search);
            
                return $this->where(function ($q) use ($attributes, $terms, $like) {
                    foreach ($terms as $term) {
                        $q->where(function ($qq) use ($attributes, $term, $like) {
                            foreach ($attributes as $attr) {
                                // Kapcsolt mező támogatás: "relation.field"
                                if (str_contains($attr, '.')) {
                                    [$relation, $relAttr] = explode('.', $attr, 2);
                                    $qq->orWhereHas($relation, function ($rq) use ($relAttr, $term, $like) {
                                        $rq->where($relAttr, $like, "%{$term}%");
                                    });
                                } else {
                                    $qq->orWhere($attr, $like, "%{$term}%");
                                }
                            }
                        });
                    }
                });
            }
        );
        
        // orWhereLike: ugyanaz, csak a legkülső feltétel OR lesz
        Builder::macro('orWhereLike', function ($attributes, string $search) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $attributes = Arr::wrap($attributes);
            $search = trim($search);
            if ($search === '') return $this;

            $driver = $this->getQuery()->getConnection()->getDriverName();
            $likeOp = $driver === 'pgsql' ? 'ilike' : 'like';
            $terms = preg_split('/\s+/', $search) ?: [$search];

            return $this->orWhere(function ($q) use ($attributes, $terms, $likeOp) {
                foreach ($terms as $term) {
                    $q->where(function ($qq) use ($attributes, $term, $likeOp) {
                        foreach ($attributes as $attr) {
                            if (str_contains($attr, '.')) {
                                [$rel, $relAttr] = explode('.', $attr, 2);
                                $qq->orWhereHas($rel, function ($rq) use ($relAttr, $term, $likeOp) {
                                    $rq->where($relAttr, $likeOp, "%{$term}%");
                                });
                            } else {
                                $qq->orWhere($attr, $likeOp, "%{$term}%");
                            }
                        }
                    });
                }
            });
        });
        
    }
}
