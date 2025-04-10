<?php

namespace App\Config;

/**
 * ConfigObject - Implementation of ConfigInterface
 * 
 * This class provides access to configuration variables
 * It should only be instantiated by the ConfigManager class
 */
class ConfigObject implements ConfigInterface
{
    private array $variables;
    
    /**
     * Constructor
     * 
     * @param array $variables Variables this instance can access
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value or default
     */
    public function get(string $key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if the key exists
     */
    public function has(string $key): bool
    {
        return isset($this->variables[$key]);
    }
    
    /**
     * Get all accessible configuration as an array
     * 
     * @return array All accessible configuration
     */
    public function all(): array
    {
        return $this->variables;
    }
}