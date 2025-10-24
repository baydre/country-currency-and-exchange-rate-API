<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when validation fails (400)
 */
class ValidationException extends Exception
{
    protected $code = 400;
    protected $errors = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param array $errors
     */
    public function __construct($message = 'Validation failed', $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
