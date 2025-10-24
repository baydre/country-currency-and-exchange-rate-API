<?php

/**
 * Helper functions for the application
 */

if (!function_exists('env')) {
    /**
     * Get environment variable value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        return $value;
    }
}

if (!function_exists('jsonResponse')) {
    /**
     * Send JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return never
     */
    function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('basePath')) {
    /**
     * Get base path of the application
     *
     * @param string $path
     * @return string
     */
    function basePath($path = '')
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
