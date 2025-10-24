<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when external service is unavailable (503)
 */
class ServiceUnavailableException extends Exception
{
    protected $code = 503;

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }
}
