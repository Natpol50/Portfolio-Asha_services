<?php

namespace App\Services;

/**
 * ValidationService - Form validation service
 * 
 * This service provides methods for validating form inputs
 * and collecting validation errors.
 */
class ValidationService
{
    private array $errors = [];
    
    /**
     * Check if a value is not empty
     * 
     * @param mixed $value Value to check
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function required($value, string $field, string $message): bool
    {
        $valid = !empty($value);
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value is a valid email
     * 
     * @param string $value Value to check
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function email(string $value, string $field, string $message): bool
    {
        $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value is a valid URL
     * 
     * @param string $value Value to check
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function url(string $value, string $field, string $message): bool
    {
        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value is a valid date
     * 
     * @param string $value Value to check
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function date(string $value, string $field, string $message): bool
    {
        $valid = strtotime($value) !== false;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value has a minimum length
     * 
     * @param string $value Value to check
     * @param int $min Minimum length
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function minLength(string $value, int $min, string $field, string $message): bool
    {
        $valid = mb_strlen($value) >= $min;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value has a maximum length
     * 
     * @param string $value Value to check
     * @param int $max Maximum length
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function maxLength(string $value, int $max, string $field, string $message): bool
    {
        $valid = mb_strlen($value) <= $max;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value is numeric
     * 
     * @param mixed $value Value to check
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function numeric($value, string $field, string $message): bool
    {
        $valid = is_numeric($value);
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value is in a list of allowed values
     * 
     * @param mixed $value Value to check
     * @param array $allowedValues Allowed values
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function inList($value, array $allowedValues, string $field, string $message): bool
    {
        $valid = in_array($value, $allowedValues, true);
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Check if a value matches a regular expression
     * 
     * @param string $value Value to check
     * @param string $pattern Regular expression pattern
     * @param string $field Field name
     * @param string $message Error message
     * @return bool True if valid
     */
    public function regex(string $value, string $pattern, string $field, string $message): bool
    {
        $valid = preg_match($pattern, $value) === 1;
        
        if (!$valid) {
            $this->addError($field, $message);
        }
        
        return $valid;
    }
    
    /**
     * Add an error message for a field
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Check if there are any validation errors
     * 
     * @return bool True if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        // Flatten errors array for display
        $flatErrors = [];
        
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flatErrors[] = $error;
            }
        }
        
        return $flatErrors;
    }
    
    /**
     * Get validation errors for a specific field
     * 
     * @param string $field Field name
     * @return array Field errors
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Reset validation errors
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->errors = [];
    }
}
