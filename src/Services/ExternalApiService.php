<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Exceptions\ServiceUnavailableException;

/**
 * Service for fetching data from external APIs
 */
class ExternalApiService
{
    private $httpClient;
    private $countriesApiUrl;
    private $exchangeRateApiUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => false, // For development, set to true in production
        ]);

        $this->countriesApiUrl = env('RESTCOUNTRIES_API', 'https://restcountries.com/v2/all');
        $this->exchangeRateApiUrl = env('EXCHANGERATE_API', 'https://open.er-api.com/v6/latest/USD');
    }

    /**
     * Fetch all countries data from RestCountries API
     *
     * @return array
     * @throws ServiceUnavailableException
     */
    public function fetchCountries()
    {
        try {
            $response = $this->httpClient->get($this->countriesApiUrl, [
                'query' => [
                    'fields' => 'name,capital,region,population,flag,currencies'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new ServiceUnavailableException(
                    'Countries API returned non-200 status code: ' . $response->getStatusCode()
                );
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ServiceUnavailableException(
                    'Failed to parse Countries API response: ' . json_last_error_msg()
                );
            }

            if (!is_array($data) || empty($data)) {
                throw new ServiceUnavailableException(
                    'Countries API returned empty or invalid data'
                );
            }

            return $data;

        } catch (GuzzleException $e) {
            throw new ServiceUnavailableException(
                'Failed to fetch countries data: ' . $e->getMessage(),
                503,
                $e
            );
        }
    }

    /**
     * Fetch exchange rates from Exchange Rate API
     *
     * @return array Associative array of currency codes to rates
     * @throws ServiceUnavailableException
     */
    public function fetchExchangeRates()
    {
        try {
            $response = $this->httpClient->get($this->exchangeRateApiUrl);

            if ($response->getStatusCode() !== 200) {
                throw new ServiceUnavailableException(
                    'Exchange Rate API returned non-200 status code: ' . $response->getStatusCode()
                );
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ServiceUnavailableException(
                    'Failed to parse Exchange Rate API response: ' . json_last_error_msg()
                );
            }

            if (!isset($data['rates']) || !is_array($data['rates'])) {
                throw new ServiceUnavailableException(
                    'Exchange Rate API returned invalid data structure'
                );
            }

            return $data['rates'];

        } catch (GuzzleException $e) {
            throw new ServiceUnavailableException(
                'Failed to fetch exchange rates: ' . $e->getMessage(),
                503,
                $e
            );
        }
    }
}
