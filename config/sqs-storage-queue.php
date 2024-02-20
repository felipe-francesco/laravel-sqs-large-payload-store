<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage prefix
    |--------------------------------------------------------------------------
    |
    | Defines the prefix (or directory) on store to save job files.
    */
    'prefix' => 'jobs',

    /*
    |--------------------------------------------------------------------------
    | Filesystem disks store (optional)
    |--------------------------------------------------------------------------
    |
    | Filesystem disks store. It is optional if it's defined on filesystem disk configuration.
    */
    'storage' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Max size
    |--------------------------------------------------------------------------
    |
    | Maximum size of payload to store on AWS SQS. If greater than that, it'll be 
    | stored on storage defined. Defaults to 256KB
    */
    'max_size' => 262144

];