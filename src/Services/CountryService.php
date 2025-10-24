<?php

namespace App\Services;

use App\Database\Database;
use App\Models\Country;
use App\Services\ExternalApiService;
use App\Services\ImageGeneratorService;
use App\Exceptions\ServiceUnavailableException;
use App\Exceptions\NotFoundException;

/**
 * Country Service - Business logic for country operations
 */
class CountryService
{
    private $externalApi;
    private $countryModel;
    private $imageGenerator;
    private $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->externalApi = new ExternalApiService();
        $this->countryModel = new Country();
        $this->imageGenerator = new ImageGeneratorService();
        $this->db = Database::getInstance();
    }

    /**
     * Refresh countries data from external APIs
     *
     * @return array
     * @throws ServiceUnavailableException
     */
    public function refreshCountries()
    {
        // Step 1: Fetch data from external APIs (fail-fast)
        $countries = $this->externalApi->fetchCountries();
        $exchangeRates = $this->externalApi->fetchExchangeRates();

        $processedCount = 0;
        $errorCount = 0;

        // Step 2: Begin database transaction
        $this->db->beginTransaction();

        try {
            // Step 3: Process each country
            foreach ($countries as $countryData) {
                try {
                    $processedData = $this->processCountryData($countryData, $exchangeRates);
                    
                    // Step 4: Upsert with case-insensitive name matching
                    $this->countryModel->upsert($processedData);
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    // Log error but continue processing other countries
                    $errorCount++;
                    error_log("Error processing country: " . $e->getMessage());
                }
            }

            // Step 5: Commit transaction
            $this->db->commit();

            // Step 6: Update global API status
            $this->countryModel->updateApiStatus();

            // Step 7: Generate summary image
            try {
                $this->imageGenerator->generateSummary();
            } catch (\Exception $e) {
                // Log image generation error but don't fail the refresh
                error_log("Error generating image: " . $e->getMessage());
            }

            return [
                'message' => 'Countries data refreshed successfully',
                'processed' => $processedCount,
                'errors' => $errorCount,
                'total_countries' => $this->countryModel->count(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            // Rollback on any error
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Process individual country data
     *
     * @param array $countryData
     * @param array $exchangeRates
     * @return array
     */
    private function processCountryData($countryData, $exchangeRates)
    {
        // Extract currency code (use first currency if multiple)
        $currencyCode = null;
        $exchangeRate = null;
        $estimatedGdp = null;

        if (!empty($countryData['currencies']) && is_array($countryData['currencies'])) {
            $firstCurrency = reset($countryData['currencies']);
            $currencyCode = $firstCurrency['code'] ?? null;

            // Match exchange rate
            if ($currencyCode && isset($exchangeRates[$currencyCode])) {
                $exchangeRate = $exchangeRates[$currencyCode];

                // Calculate estimated GDP with random multiplier (1000-2000)
                $population = (int) $countryData['population'];
                $randomMultiplier = rand(1000, 2000);
                
                if ($exchangeRate > 0) {
                    $estimatedGdp = ($population * $randomMultiplier) / $exchangeRate;
                }
            }
        }

        return [
            'name' => $countryData['name'] ?? 'Unknown',
            'capital' => $countryData['capital'] ?? null,
            'region' => $countryData['region'] ?? null,
            'population' => (int) ($countryData['population'] ?? 0),
            'currency_code' => $currencyCode,
            'exchange_rate' => $exchangeRate,
            'estimated_gdp' => $estimatedGdp,
            'flag_url' => $countryData['flag'] ?? null,
        ];
    }

    /**
     * Get all countries with optional filtering and sorting
     *
     * @param array $filters
     * @param string|null $sort
     * @return array
     */
    public function getAllCountries($filters = [], $sort = null)
    {
        return $this->countryModel->all($filters, $sort);
    }

    /**
     * Get country by name (case-insensitive)
     *
     * @param string $name
     * @return array
     * @throws NotFoundException
     */
    public function getCountryByName($name)
    {
        $country = $this->countryModel->findByName($name);
        
        if (!$country) {
            throw new NotFoundException("Country '{$name}' not found");
        }

        return $country;
    }

    /**
     * Delete country by name
     *
     * @param string $name
     * @return bool
     * @throws NotFoundException
     */
    public function deleteCountryByName($name)
    {
        return $this->countryModel->deleteByName($name);
    }

    /**
     * Get API status
     *
     * @return array
     */
    public function getStatus()
    {
        $status = $this->countryModel->getApiStatus();
        
        if (!$status) {
            return [
                'total_countries' => 0,
                'last_refreshed_at' => null
            ];
        }

        return [
            'total_countries' => (int) $status['total_countries'],
            'last_refreshed_at' => $status['last_refreshed_at']
        ];
    }
}
