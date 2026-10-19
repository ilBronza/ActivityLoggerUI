<?php

namespace IlBronza\ActivityLoggerUI\Http\Controllers;

use IlBronza\ActivityLoggerUI\ActivityLoggerUI;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CrudActivityController extends Controller
{
    public function history(Request $request, string $model, string $id, ActivityLoggerUI $history)
    {
        return $history->view($history->findModel($model, $id), $request->query());
    }

    public function activityIndex(Request $request, string $classBasename, string $primary, ActivityLoggerUI $history)
    {
        return $this->history($request, $classBasename, $primary, $history);
    }
}
