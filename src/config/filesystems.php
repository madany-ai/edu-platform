<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'b2' => [
            'driver' => 's3',
            'key' => env('BACKBLAZE_KEY_ID'),
            'secret' => env('BACKBLAZE_APPLICATION_KEY'),
            'region' => env('BACKBLAZE_REGION'),
            'bucket' => env('BACKBLAZE_BUCKET'),
            'endpoint' => env('BACKBLAZE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
        ],

        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ROOT_USER', 'lms_minio_admin'),
            'secret' => env('MINIO_ROOT_PASSWORD', 'lms_minio_secret'),
            'region' => 'us-east-1',
            'bucket' => env('MINIO_BUCKET', 'lms-videos'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
            'url' => env('MINIO_URL', 'http://localhost:9000/lms-videos'),
            'use_path_style_endpoint' => true,
            'throw' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
