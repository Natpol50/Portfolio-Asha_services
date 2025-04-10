<?php

namespace App\Core;

/**
 * RequestObject - Represents an HTTP request with user and language information
 * 
 * This class encapsulates the HTTP request data and provides
 * methods to access user authentication and language preference.
 */
class RequestObject
{
    private array $userInfo;
    private string $languageCode;
    
    /**
     * Create a new RequestObject instance
     * 
     * @param array $userInfo Optional user information
     * @param string $languageCode Optional language code
     */
    public function __construct(array $userInfo = [], string $languageCode = '')
    {
        $this->userInfo = $userInfo;
        $this->languageCode = $languageCode ?: ($_ENV['DEFAULT_LANGUAGE'] ?? 'en');
    }
    
    /**
     * Check if the user is authenticated
     * 
     * @return bool True if the user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->userInfo);
    }
    
    /**
     * Get user information
     * 
     * @return array User information
     */
    public function getUserInfo(): array
    {
        return $this->userInfo;
    }
    
    /**
     * Get user ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    public function getUserId(): ?int
    {
        return $this->userInfo['userId'] ?? null;
    }
    
    /**
     * Get user's full name
     * 
     * @return string|null User's full name or null if not authenticated
     */
    public function getUserFullName(): ?string
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->userInfo['userFirstName'] . ' ' . $this->userInfo['userName'];
    }
    
    /**
     * Check if user has a specific permission
     * 
     * @param int $permission Permission bit
     * @return bool True if user has the permission
     */
    public function hasPermission(int $permission): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $permissionInteger = $this->userInfo['permissionInteger'] ?? 0;
        
        return ($permissionInteger & $permission) === $permission;
    }
    
    /**
     * Get the current language code
     * 
     * @return string Language code (e.g., 'en', 'fr')
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
    
    /**
     * Set the language code
     * 
     * @param string $languageCode Language code
     * @return self This instance for method chaining
     */
    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }
    
    /**
     * Get the value of a query parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value or default
     */
    public function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get the value of a POST parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value or default
     */
    public function getPost(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get all POST parameters
     * 
     * @return array All POST parameters
     */
    public function getAllPost(): array
    {
        return $_POST;
    }
    
    /**
     * Get session messages
     * 
     * @param string $type Message type
     * @return array Session messages
     */
    public function getFlashMessages(string $type = 'success'): array
    {
        $messages = $_SESSION[$type] ?? [];
        unset($_SESSION[$type]);
        return $messages;
    }
}
