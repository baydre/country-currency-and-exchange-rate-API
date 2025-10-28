<?php

namespace App;

use App\Controllers\CountryController;
use App\Controllers\StatusController;
use App\Controllers\ImageController;

class Router
{
    private $requestMethod;
    private $requestUri;
    private $routes = [];

    public function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $this->registerRoutes();
    }

    /**
     * Register all application routes
     */
    private function registerRoutes()
    {
        // Base routes
        $this->get('/', [$this, 'home']);
        $this->get('/health', [$this, 'healthCheck']);
        
        // Documentation routes
        $this->get('/docs', [$this, 'serveDocs']);
        $this->get('/openapi.yaml', [$this, 'serveOpenApi']);
        
        // Country routes
        $this->post('/countries/refresh', [CountryController::class, 'refresh']);
        $this->get('/countries/image', [ImageController::class, 'show']); // Must come before {name} route
        $this->get('/countries', [CountryController::class, 'index']);
        $this->get('/countries/{name}', [CountryController::class, 'show']);
        $this->delete('/countries/{name}', [CountryController::class, 'destroy']);
        
        // Status route
        $this->get('/status', [StatusController::class, 'status']);
    }

    /**
     * Serve API documentation
     */
    public function serveDocs($params = [])
    {
        $docsPath = __DIR__ . '/../public/docs.html';
        
        if (!file_exists($docsPath)) {
            jsonResponse([
                'error' => 'Documentation not found'
            ], 404);
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($docsPath);
        exit;
    }

    /**
     * Serve OpenAPI YAML specification
     */
    public function serveOpenApi($params = [])
    {
        $openApiPath = __DIR__ . '/../public/openapi.yaml';
        
        if (!file_exists($openApiPath)) {
            jsonResponse([
                'error' => 'OpenAPI specification not found'
            ], 404);
        }

        header('Content-Type: application/yaml; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        readfile($openApiPath);
        exit;
    }

    /**
     * Home page - API information
     */
    public function home($params = [])
    {
        jsonResponse([
            'name' => 'Country & Currency Data Caching API',
            'version' => '1.0.0',
            'description' => 'A RESTful API that provides cached, processed country and currency data with calculated GDP estimates',
            'documentation' => [
                'interactive' => '/docs',
                'openapi_spec' => '/openapi.yaml'
            ],
            'endpoints' => [
                'POST /countries/refresh' => 'Refresh country data from external APIs',
                'GET /countries' => 'Get all countries (supports filtering and sorting)',
                'GET /countries/{name}' => 'Get a specific country by name',
                'DELETE /countries/{name}' => 'Delete a country by name',
                'GET /status' => 'Get API status and metadata',
                'GET /countries/image' => 'Get summary image',
                'GET /health' => 'Health check endpoint'
            ],
            'status' => 'operational',
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }

    /**
     * Health check endpoint
     */
    public function healthCheck($params = [])
    {
        // Check database connection
        try {
            $db = \App\Database\Database::getInstance();
            $db->getConnection()->query('SELECT 1');
            $dbStatus = 'healthy';
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        // Check cache directory
        $cacheDir = basePath(env('CACHE_DIR', 'cache'));
        $cacheStatus = is_writable($cacheDir) ? 'healthy' : 'unhealthy';

        $overallStatus = ($dbStatus === 'healthy' && $cacheStatus === 'healthy') ? 'healthy' : 'unhealthy';

        jsonResponse([
            'status' => $overallStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [
                'database' => $dbStatus,
                'cache_directory' => $cacheStatus
            ],
            'uptime' => true
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Register GET route
     */
    private function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    private function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register DELETE route
     */
    private function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add route to routes array
     */
    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Dispatch the request to appropriate controller
     */
    public function dispatch()
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->requestMethod) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $this->requestUri);
            
            if ($params !== false) {
                [$controller, $method] = $route['handler'];
                $controllerInstance = new $controller();
                
                // Pass params as a single array argument
                call_user_func([$controllerInstance, $method], $params);
                return;
            }
        }

        // No route matched - 404
        jsonResponse([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found'
        ], 404);
    }

    /**
     * Match route pattern with actual URI
     * 
     * @param string $pattern
     * @param string $uri
     * @return array|false
     */
    private function matchRoute($pattern, $uri)
    {
        // Convert pattern to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract only named parameters
            return array_filter($matches, function($key) {
                return !is_numeric($key);
            }, ARRAY_FILTER_USE_KEY);
        }

        return false;
    }
}
