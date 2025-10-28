<?php

namespace App\Controllers;

use App\Services\CountryService;
use App\Exceptions\ServiceUnavailableException;
use App\Exceptions\NotFoundException;

/**
 * Country Controller - Handle country-related HTTP requests
 */
class CountryController
{
    private $countryService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countryService = new CountryService();
    }

    /**
     * POST /countries/refresh
     * Refresh countries data from external APIs
     *
     * @return never
     */
    public function refresh()
    {
        try {
            $result = $this->countryService->refreshCountries();
            jsonResponse($result, 200);
            
        } catch (ServiceUnavailableException $e) {
            jsonResponse([
                'error' => 'External data source unavailable',
                'message' => $e->getMessage()
            ], 503);
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to refresh countries data'
            ], 500);
        }
    }

    /**
     * GET /countries
     * Get all countries with optional filtering and sorting
     *
     * @return never
     */
    public function index()
    {
        // Parse query parameters
        $filters = [];
        
        if (!empty($_GET['region'])) {
            $filters['region'] = $_GET['region'];
        }
        
        if (!empty($_GET['currency'])) {
            $filters['currency'] = strtoupper($_GET['currency']);
        }
        
        $sort = $_GET['sort'] ?? null;
        
        // Validate sort parameter
        if ($sort && !in_array($sort, ['gdp_desc'])) {
            jsonResponse([
                'error' => 'Invalid sort parameter',
                'message' => 'Allowed values: gdp_desc'
            ], 400);
        }

        try {
            $countries = $this->countryService->getAllCountries($filters, $sort);
            jsonResponse($countries, 200);
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to fetch countries'
            ], 500);
        }
    }

    /**
     * GET /countries/{name}
     * Get single country by name
     *
     * @param array $params
     * @return never
     */
    public function show($params = [])
    {
        $name = $params['name'] ?? null;
        
        if (!$name) {
            jsonResponse([
                'error' => 'Country name is required'
            ], 400);
        }

        try {
            $country = $this->countryService->getCountryByName($name);
            jsonResponse($country, 200);
            
        } catch (NotFoundException $e) {
            jsonResponse([
                'error' => 'Country not found',
                'message' => $e->getMessage()
            ], 404);
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to fetch country'
            ], 500);
        }
    }

    /**
     * DELETE /countries/{name}
     * Delete country by name
     *
     * @param array $params
     * @return never
     */
    public function destroy($params = [])
    {
        $name = $params['name'] ?? null;
        
        if (!$name) {
            jsonResponse([
                'error' => 'Country name is required'
            ], 400);
        }

        try {
            $this->countryService->deleteCountryByName($name);
            jsonResponse([
                'message' => "Country '{$name}' deleted successfully"
            ], 200);
            
        } catch (NotFoundException $e) {
            jsonResponse([
                'error' => 'Country not found',
                'message' => $e->getMessage()
            ], 404);
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to delete country'
            ], 500);
        }
    }
}
