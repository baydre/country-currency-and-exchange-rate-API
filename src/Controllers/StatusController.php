<?php

namespace App\Controllers;

use App\Services\CountryService;

/**
 * Status Controller - Handle API status requests
 */
class StatusController
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
     * GET /status
     * Get API status information
     *
     * @return never
     */
    public function status()
    {
        try {
            $status = $this->countryService->getStatus();
            jsonResponse($status, 200);
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to fetch status'
            ], 500);
        }
    }
}
