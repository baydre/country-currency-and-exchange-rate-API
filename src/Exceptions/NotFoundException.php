<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when resource is not found (404)
 */
class NotFoundException extends Exception
{
    protected $code = 404;

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
