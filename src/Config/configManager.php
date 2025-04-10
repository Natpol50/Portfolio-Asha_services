<?php

namespace App\Config;

/**
 * ConfigManager - Secure configuration management using Reflection API
 * 
 * This class provides controlled access to environment variables from .env files
 * using PHP's Reflection API to verify the requesting class.
 */
class ConfigManager
{
    private static ?ConfigManager $instance = null;
    private array $variables = [];
    private array $accessMap = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Load environment variables using PHP dotenv
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
        
        // Store all variables in our private array
        foreach ($_ENV as $key => $value) {
            $this->variables[$key] = $value;
        }
        
        $this->initializeAccessMap();
    }
    
    /**
     * Define which classes can access which variables
     */
    private function initializeAccessMap(): void
    {
        // Define access permissions - which classes can access which env variables
        $this->accessMap = [
            'App\\Services\\Database' => [
                'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_PORT'
            ],
            'App\\Services\\TokenService' => [
                'JWT_SECRET', 'JWT_NAME', 'JWT_EXPIRY', 'JWT_REFRESH_EXPIRY'
            ],
            'App\\Core\\Application' => [
                'APP_ENV', 'APP_DEBUG', 'APP_URL', 'STATIC_URL'
            ]
        ];
    }
    
    /**
     * Prevent cloning of the singleton instance
     */
    private function __clone(): void {}
    
    /**
     * Prevent unserializing of the singleton instance
     */
    public function __wakeup(): void
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get the singleton instance
     * 
     * @return ConfigManager The singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get configuration access for a specific object using Reflection
     * 
     * @param object $service The object requesting configuration
     * @return ConfigInterface A restricted configuration access object
     * @throws \Exception If the calling class is not authorized
     */
    public function getConfigFor(object $service): ConfigInterface
    {
        // Use Reflection to get the actual class of the calling object
        $reflection = new \ReflectionObject($service);
        $className = $reflection->getName();
        
        // Get parent class if exists - this helps with inheritance
        if (!isset($this->accessMap[$className])) {
            $parentClass = $reflection->getParentClass();
            if ($parentClass) {
                $className = $parentClass->getName();
            }
        }
        
        // Check if this class is allowed in the access map
        if (!isset($this->accessMap[$className])) {
            throw new \Exception("Class '$className' is not authorized to access configuration");
        }
        
        // Get the allowed keys for this class
        $allowedKeys = $this->accessMap[$className];
        $accessibleVariables = [];
        
        // Filter the variables based on allowed keys
        foreach ($allowedKeys as $key) {
            if (isset($this->variables[$key])) {
                $accessibleVariables[$key] = $this->variables[$key];
            }
        }
        
        // Return a ConfigObject with only the allowed variables
        return new ConfigObject($accessibleVariables);
    }
}