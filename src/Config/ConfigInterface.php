<?php

namespace App\Config;

/**
 * ConfigInterface - Interface for configuration access, making it easier to mock
 * 
 * This interface defines the methods that configuration access objects must implement
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     * 
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, $default = null);
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key The configuration key
     * @return bool True if the key exists
     */
    public function has(string $key): bool;
}