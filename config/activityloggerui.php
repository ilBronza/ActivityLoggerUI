<?php

return [
    'middleware' => ['web', 'auth'],
    'route_prefix' => 'activity-log-management',
    // null uses the self-contained layout; e.g. 'uikittemplate::app' to embed it.
    'layout' => null,
    'per_page' => 25,
    // Used for models without CRUD's userCanShow().
    'authorization_ability' => 'view',
    'excluded_fields' => ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'],
];
