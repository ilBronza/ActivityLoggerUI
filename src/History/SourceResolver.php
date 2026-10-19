<?php

namespace IlBronza\ActivityLoggerUI\History;

use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Casts\ExtraFieldJson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use LogicException;

class SourceResolver
{
    public function canView(Model $model): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return method_exists($model, 'userCanShow')
            ? (bool) $model->userCanShow(auth()->user())
            : Gate::allows(config('activityloggerui.authorization_ability', 'view'), $model);
    }

    public function resolve(Model $model): Collection
    {
        $excluded = $this->excluded($model);
        $sources = collect([new HistorySource($model, 'model', __('activityloggerui::history.model'), null, $excluded)]);

        // Read cast metadata and relations only. ExtraField getters/providers may
        // create storage records and must never run during a history request.
        $casts = method_exists($model, 'getExtraFieldsCasts') ? $model->getExtraFieldsCasts() : [];
        $relations = [];
        $unsupported = [];
        foreach ($casts as $field => $cast) {
            if (! is_string($cast)) {
                $unsupported[] = $field;
                continue;
            }
            [$class, $parameters] = array_pad(explode(':', $cast, 2), 2, '');
            if (str_contains($class, 'ExtraFieldParameter')
                || (! is_a($class, ExtraField::class, true) && ! is_a($class, ExtraFieldJson::class, true))) {
                $unsupported[] = $field;
                continue;
            }
            $arguments = explode(',', $parameters);
            $relation = $arguments[0] ?: 'extraFields';
            $storageField = is_a($class, ExtraField::class, true) ? ($arguments[1] ?? $field) : $field;
            $relations[$relation][$storageField ?: $field][] = $field;
        }

        foreach ($relations as $relation => $fields) {
            foreach ($this->related($model, $relation) as $related) {
                $sources->push(new HistorySource(
                    $related, 'extra:' . $relation, __('activityloggerui::history.extra_fields') . ' · ' . $relation,
                    $fields, array_unique(array_merge($excluded, $this->excluded($related)))
                ));
            }
        }

        foreach (method_exists($model, 'getActivityHistoryRelations') ? $model->getActivityHistoryRelations() : [] as $relation) {
            foreach ($this->related($model, $relation) as $related) {
                if ($this->canView($related)) {
                    $sources->push(new HistorySource($related, 'relation:' . $relation, $relation,
                        null, array_unique(array_merge($excluded, $this->excluded($related)))));
                }
            }
        }

        // Extension point for dossier/parameter storage or previously linked subjects.
        foreach (method_exists($model, 'getActivityHistorySources') ? $model->getActivityHistorySources() : [] as $source) {
            if (! $source instanceof HistorySource) {
                throw new LogicException('getActivityHistorySources() must return HistorySource instances.');
            }
            if ($this->canView($source->model)) {
                $source->excluded = array_unique(array_merge($source->excluded, $excluded, $this->excluded($source->model)));
                $sources->push($source);
            }
        }

        return collect(['sources' => $sources, 'unsupported' => $unsupported]);
    }

    protected function related(Model $model, string $name): Collection
    {
        if (! method_exists($model, $name) || ! ($model->$name() instanceof Relation)) {
            throw new LogicException("History source [{$name}] must be a direct Eloquent relation.");
        }
        $model->loadMissing($name);
        $value = $model->getRelation($name);

        return ($value instanceof Model ? collect([$value]) : collect($value))
            ->filter(fn ($related) => $related instanceof Model && $related->getKey() !== null);
    }

    protected function excluded(Model $model): array
    {
        return array_unique(array_merge(
            [$model->getKeyName()], $model->getHidden(),
            config('activityloggerui.excluded_fields', []),
            method_exists($model, 'getHistoryExcludedFields') ? $model->getHistoryExcludedFields() : [],
        ));
    }
}
