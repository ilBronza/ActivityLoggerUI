<?php

namespace IlBronza\ActivityLoggerUI\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LoggedRecord extends Model
{
    use LogsActivity;

    protected $table = 'records';
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name'])->logOnlyDirty();
    }
}
