<?php

namespace IlBronza\ActivityLoggerUI\History;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActivityPresenter
{
    public function present(Model $activity, HistorySource $source, ?string $field = null): array
    {
        $properties = $this->normalize($activity->properties);
        $old = $this->normalize($properties['old'] ?? []);
        $new = $this->normalize($properties['attributes'] ?? []);
        $changes = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $storageField) {
            foreach ($source->publicFields((string) $storageField) as $publicField) {
                if ($field !== null && $field !== $publicField) {
                    continue;
                }
                $oldLogged = array_key_exists($storageField, $old);
                $newLogged = array_key_exists($storageField, $new);
                if ($oldLogged && $newLogged && $old[$storageField] === $new[$storageField]) {
                    continue;
                }
                $changes[] = [
                    'field' => $publicField, 'label' => $this->fieldLabel($publicField),
                    'old' => $oldLogged ? $this->value($old[$storageField]) : __('activityloggerui::history.not_recorded'),
                    'new' => $newLogged ? $this->value($new[$storageField]) : __('activityloggerui::history.not_recorded'),
                    'old_logged' => $oldLogged, 'new_logged' => $newLogged,
                ];
            }
        }

        $event = $activity->event ?: $activity->description;
        $eventKey = 'activityloggerui::history.events.' . $event;
        $causer = $activity->getRelation('causer');

        return [
            'id' => $activity->getKey(),
            'date' => $activity->created_at?->format('d/m/Y H:i:s'),
            'datetime' => $activity->created_at?->toIso8601String(),
            'event' => $event,
            'event_label' => __($eventKey) === $eventKey ? $event : __($eventKey),
            'causer' => $causer ? $this->name($causer) : ($activity->causer_id !== null
                ? __('activityloggerui::history.unknown_actor', ['id' => $activity->causer_id])
                : __('activityloggerui::history.system')),
            'source' => $source->label,
            'subject_id' => $activity->subject_id,
            'changes' => $changes,
        ];
    }

    public function loadCausers(Collection $activities): void
    {
        // Include missing authors, preventing MorphTo from querying the log DB.
        foreach ($activities as $activity) {
            $activity->setRelation('causer', null);
        }
        foreach ($activities->whereNotNull('causer_id')->groupBy('causer_type') as $type => $group) {
            $class = Relation::getMorphedModel($type) ?? $type;
            if (! is_subclass_of($class, Model::class)) {
                continue;
            }
            $causers = (new $class)->newQuery()->whereKey($group->pluck('causer_id')->unique()->all())->get()
                ->keyBy(fn ($causer) => (string) $causer->getKey());
            foreach ($group as $activity) {
                $activity->setRelation('causer', $causers->get((string) $activity->causer_id));
            }
        }
    }

    public function name(Model $model): string
    {
        // Avoid extra-field accessors that may create storage records.
        $attributes = $model->getAttributes();
        foreach (['name', 'title', 'rag_soc', 'email'] as $field) {
            if (! in_array($field, $model->getHidden(), true) && is_scalar($attributes[$field] ?? null)) {
                return (string) $attributes[$field];
            }
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    public function fieldLabel(string $field): string
    {
        $key = 'fields.' . $field;

        return __($key) === $key ? Str::ucfirst(str_replace('_', ' ', $field)) : __($key);
    }

    protected function normalize(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }

    protected function value(mixed $value): string
    {
        if ($value === null) {
            return __('activityloggerui::history.null');
        }
        if ($value === '') {
            return __('activityloggerui::history.empty_string');
        }
        if (is_bool($value)) {
            return __('activityloggerui::history.' . ($value ? 'yes' : 'no'));
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '';
        }

        return (string) $value;
    }
}
