<?php

namespace IlBronza\ActivityLoggerUI;

use IlBronza\ActivityLoggerUI\History\ActivityPresenter;
use IlBronza\ActivityLoggerUI\History\HistorySource;
use IlBronza\ActivityLoggerUI\History\SourceResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class ActivityLoggerUI
{
    public function __construct(protected SourceResolver $resolver, protected ActivityPresenter $presenter)
    {
    }

    public function findModel(string $type, string $key): Model
    {
        $class = Relation::getMorphedModel($type) ?? $type;
        abort_unless(is_subclass_of($class, Model::class), 404);
        if (method_exists($class, 'gpc')) {
            $class = $class::gpc();
        }
        abort_unless(is_string($class) && is_subclass_of($class, Model::class), 404);

        return $class::query()->findOrFail($key);
    }

    public function url(Model $model, array $filters = []): string
    {
        return route('activityloggerui.history', [
            'model' => $model->getMorphClass(), 'id' => $model->getKey(),
        ] + $filters);
    }

    public function view(Model $model, array $filters = [], bool $partial = false): View
    {
        return view($partial ? 'activityloggerui::history' : 'activityloggerui::index', $this->data($model, $filters));
    }

    public function data(Model $model, array $filters = []): array
    {
        abort_unless($this->resolver->canView($model), 403);
        $resolved = $this->resolver->resolve($model);
        $sources = $resolved['sources'];
        $options = $sources->mapWithKeys(fn (HistorySource $source) => [$source->key => $source->label])->all();
        $filters = Validator::make($filters, [
            'source' => ['nullable', 'string', Rule::in(array_keys($options))],
            'event' => ['nullable', 'string', 'max:100'],
            'field' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...(! empty($filters['from']) ? ['after_or_equal:from'] : [])],
            'page' => ['nullable', 'integer', 'min:1'],
        ])->validate();
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        if (isset($filters['source'])) {
            $sources = $sources->where('key', $filters['source']);
        }
        $field = $filters['field'] ?? null;
        $activityClass = config('activitylog.activity_model', Activity::class);
        $query = $activityClass::query()->where(function ($query) use ($sources, $field) {
            $query->whereRaw('1 = 0');
            // Group subjects to avoid one OR branch for every row of a hasMany.
            $groups = $sources->groupBy(fn ($source) => json_encode([
                $source->model->getMorphClass(), $field === null ? null : $source->storageFields($field),
            ]));
            foreach ($groups as $group) {
                $source = $group->first();
                $storageFields = $field === null ? null : $source->storageFields($field);
                if ($storageFields === []) {
                    continue;
                }
                $query->orWhere(function ($query) use ($source, $group, $storageFields) {
                    $query->where('subject_type', $source->model->getMorphClass())
                        ->whereIn('subject_id', $group->map(fn ($item) => $item->model->getKey())->unique()->all());
                    if ($storageFields !== null) {
                        $query->where(function ($query) use ($storageFields) {
                            foreach ($storageFields as $storageField) {
                                $query->orWhereJsonContainsKey('properties->old->' . $storageField)
                                    ->orWhereJsonContainsKey('properties->attributes->' . $storageField);
                            }
                        });
                    }
                });
            }
        });
        if (isset($filters['event'])) {
            $query->where(function ($query) use ($filters) {
                $query->where('event', $filters['event'])->orWhere(function ($query) use ($filters) {
                    $query->whereNull('event')->where('description', $filters['event']);
                });
            });
        }
        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $perPage = max(1, min(100, (int) config('activityloggerui.per_page', 25)));
        $activities = $query->orderByDesc('created_at')->orderByDesc((new $activityClass)->getKeyName())
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1))
            ->withPath($this->url($model))->appends(collect($filters)->except('page')->all());
        $this->presenter->loadCausers($activities->getCollection());
        $entries = $activities->getCollection()->map(function ($activity) use ($sources, $field) {
            $matching = $sources->filter(fn ($source) => $source->matches($activity)
                && ($field === null || $source->storageFields($field) !== []));
            $presented = $matching->map(fn ($source) => $this->presenter->present($activity, $source, $field));
            $entry = $presented->first();
            // Two ExtraField relations can point to the same storage record.
            // Keep one event, with all public field mappings represented.
            $entry['changes'] = $presented->flatMap(fn ($item) => $item['changes'])->unique('field')->values()->all();
            $entry['source'] = $matching->pluck('label')->unique()->implode(' / ');

            return $entry;
        });

        return [
            'model' => $model, 'modelName' => $this->presenter->name($model),
            'activities' => $activities, 'entries' => $entries,
            'sources' => $options, 'filters' => $filters,
            'unsupportedFields' => $resolved['unsupported'], 'historyUrl' => $this->url($model),
        ];
    }
}
