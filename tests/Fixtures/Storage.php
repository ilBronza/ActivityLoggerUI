<?php

namespace IlBronza\ActivityLoggerUI\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    protected $table = 'storages';
    protected $guarded = [];
    protected $hidden = ['storage_secret'];
}
