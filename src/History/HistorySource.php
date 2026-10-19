<?php

namespace IlBronza\ActivityLoggerUI\History;

use Illuminate\Database\Eloquent\Model;

/** A recorded subject, with an optional storage-column => public-field map. */
class HistorySource
{
    public function __construct(
        public Model $model,
        public string $key = 'model',
        public string $label = '',
        public ?array $fields = null,
        public array $excluded = [],
    ) {
    }

    public function matches(Model $activity): bool
    {
        return $activity->subject_type === $this->model->getMorphClass()
            && (string) $activity->subject_id === (string) $this->model->getKey();
    }

    public function publicFields(string $storageField): array
    {
        $fields = $this->fields === null ? [$storageField] : (array) ($this->fields[$storageField] ?? []);
        if (in_array($storageField, $this->excluded, true)) {
            return [];
        }

        return array_values(array_diff($fields, $this->excluded));
    }

    public function storageFields(string $publicField): array
    {
        if ($this->fields === null) {
            return $this->publicFields($publicField) ? [$publicField] : [];
        }

        return array_values(array_filter(array_keys($this->fields),
            fn ($field) => in_array($publicField, $this->publicFields($field), true)));
    }
}
