<?php
use Cake\Log\Engine\FileLog;

var_export('events'.date('.Y.m.W'));
return [
    /*
 * Configures logging options
 */
    'Log' => [
        'stack_cache_expiry' => [
            'className' => FileLog::class,
            'path' => LOGS . 'stack_cache_expiry' . DS,
            'file' => 'events'.date('.Y.m.') . 'week' . date('W') . '.log',
//            'url' => env('LOG_DEBUG_URL', null),
//            'scopes' => false,
            'levels' => ['info'],
        ],
    ],
];
