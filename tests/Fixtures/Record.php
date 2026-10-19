<?php

namespace IlBronza\ActivityLoggerUI\Tests\Fixtures;

use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Casts\ExtraFieldDate;
use IlBronza\CRUD\Models\Casts\ExtraFieldJson;
use IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use CRUDModelExtraFieldsTrait;

    protected $table = 'records';
    protected $guarded = [];
    protected $hidden = ['secret'];
    protected $casts = [
        'note' => ExtraField::class,
        'due_date' => ExtraFieldDate::class,
        'settings' => ExtraFieldJson::class,
        'city' => ExtraField::class . ':address,stored_city',
        'private_city' => ExtraField::class . ':address,private_storage',
    ];

    public function getExtraFieldsClass(): ?string
    {
        return Storage::class;
    }

    public function address()
    {
        return $this->hasOne(Storage::class, 'record_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getActivityHistoryRelations(): array
    {
        return ['children', 'peers'];
    }

    public function peers()
    {
        return $this->belongsToMany(self::class, 'record_links', 'parent_record_id', 'peer_record_id');
    }

    public function getHistoryExcludedFields(): array
    {
        return ['private_city'];
    }

    public function userCanShow($user): bool
    {
        return (bool) $this->getAttribute('visible');
    }
}
