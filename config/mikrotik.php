<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MikroTik API Settings
    |--------------------------------------------------------------------------
    |
    | These configuration options control the behavior of MikroTik API 
    | connections and timeout handling throughout the application.
    |
    */

    'timeouts' => [
        // Connection timeout in seconds
        'connection' => env('MIKROTIK_CONNECTION_TIMEOUT', 15),
        
        // Data retrieval timeout in seconds  
        'data_retrieval' => env('MIKROTIK_DATA_TIMEOUT', 120),
        
        // PPP secrets sync timeout in seconds
        'ppp_sync' => env('MIKROTIK_PPP_SYNC_TIMEOUT', 300),
        
        // Interface query timeout in seconds
        'interface_query' => env('MIKROTIK_INTERFACE_TIMEOUT', 30),
        
        // Ping test timeout in seconds
        'ping_test' => env('MIKROTIK_PING_TIMEOUT', 30),
    ],

    'retry' => [
        // Maximum number of retry attempts
        'max_attempts' => env('MIKROTIK_MAX_RETRIES', 3),
        
        // Base delay between retries in seconds
        'base_delay' => env('MIKROTIK_RETRY_DELAY', 2),
        
        // Whether to use exponential backoff for retries
        'exponential_backoff' => env('MIKROTIK_EXPONENTIAL_BACKOFF', true),
    ],

    'chunking' => [
        // Default chunk size for large data retrievals
        'default_chunk_size' => env('MIKROTIK_DEFAULT_CHUNK_SIZE', 10),
        
        // Maximum chunk size
        'max_chunk_size' => env('MIKROTIK_MAX_CHUNK_SIZE', 50),
        
        // Minimum chunk size 
        'min_chunk_size' => env('MIKROTIK_MIN_CHUNK_SIZE', 5),
        
        // Enable automatic chunking for large datasets
        'auto_chunking' => env('MIKROTIK_AUTO_CHUNKING', true),
    ],

    'fallback' => [
        // Use demo/fallback data when connection fails
        'enable_demo_data' => env('MIKROTIK_ENABLE_DEMO_DATA', true),
        
        // Cache timeout for fallback data in minutes
        'cache_timeout' => env('MIKROTIK_CACHE_TIMEOUT', 5),
    ],

    'performance' => [
        // Use minimal property lists to reduce data transfer
        'use_minimal_proplist' => env('MIKROTIK_USE_MINIMAL_PROPLIST', true),
        
        // Enable connection pooling/reuse
        'connection_pooling' => env('MIKROTIK_CONNECTION_POOLING', false),
        
        // Maximum execution time for PHP scripts in seconds
        'max_execution_time' => env('MIKROTIK_MAX_EXECUTION_TIME', 300),
    ],

    'logging' => [
        // Enable detailed logging for debugging
        'detailed_logging' => env('MIKROTIK_DETAILED_LOGGING', true),
        
        // Log performance metrics
        'log_performance' => env('MIKROTIK_LOG_PERFORMANCE', true),
        
        // Log connection attempts
        'log_connections' => env('MIKROTIK_LOG_CONNECTIONS', true),
    ],
];
