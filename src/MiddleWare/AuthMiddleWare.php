<?php

namespace App\Middleware;

use App\Services\TokenService;
use App\Services\CacheService;
use App\Models\UserModel;
use App\Core\RequestObject;
use App\Exceptions\AuthenticationException;

/**
 * AuthMiddleware - Authentication and authorization middleware
 * 
 * This middleware handles user authentication and authorization.
 * It validates JWT tokens, retrieves user permissions, and builds
 * the RequestObject for controllers.
 */
class AuthMiddleware
{
    private TokenService $tokenService;
    private CacheService $cacheService;
    private UserModel $userModel;
    
    /**
     * Create a new AuthMiddleware instance
     * 
     * @param TokenService $tokenService Service for JWT token handling
     * @param CacheService $cacheService Service for caching
     */
    public function __construct(TokenService $tokenService, CacheService $cacheService)
    {
        $this->tokenService = $tokenService;
        $this->cacheService = $cacheService;
        $this->userModel = new UserModel();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in
     */
    public function isUserLoggedIn(): bool
    {
        $tokenName = $this->tokenService->getTokenName();
        
        if (!isset($_COOKIE[$tokenName])) {
            return false;
        }
        
        $token = $_COOKIE[$tokenName];
        
        try {
            return $this->tokenService->validateAndRefreshToken($token);
        } catch (AuthenticationException $e) {
            // Log the exception
            error_log("Authentication failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve user information from token and permissions from cache
     * 
     * @param int $userId User ID
     * @param int $userRole User role ID
     * @return array User information with permissions
     */
    public function retrieveUserInfo(int $userId, int $userRole): array
    {
        // Get role permissions from cache
        $permissions = $this->cacheService->getRolePermission($userRole);
        $permissionInt = $this->calculatePermissionInteger($permissions);
        
        // Get additional user data
        $userData = $this->userModel->getUserById($userId);
        
        if (!$userData) {
            return [
                'userId' => $userId,
                'userName' => 'Unknown',
                'userFirstName' => 'Unknown',
                'permissionInteger' => 0,
                'userRole' => $userRole,
                'profilePictureUrl' => '/assets/img/default-avatar.png',
                'userSearchType' => null,
            ];
        }
        
        return [
            'userId' => $userId,
            'userName' => $userData->userName,
            'userFirstName' => $userData->userFirstName,
            'permissionInteger' => $permissionInt,
            'userRole' => $userRole,
            'profilePictureUrl' => $userData->profilePictureUrl,
            'userSearchType' => $userData->userSearchType,
        ];
    }
    
    /**
     * Calculate permission integer from individual permissions
     * 
     * @param array $permissions Array of permission flags
     * @return int Bit-encoded permission integer
     */
    private function calculatePermissionInteger(array $permissions): int
    {
        $permissionInt = 0;
        $power = 0;
        
        // Skip first two attributes (id and name)
        foreach (array_slice($permissions, 2) as $permission) {
            if ($permission == 1) {
                $permissionInt += 2 ** $power;
            }
            $power++;
        }
        
        return $permissionInt;
    }
    
    /**
     * Process the request through authentication
     * 
     * @return RequestObject|null The authenticated request object or null
     */
    public function handle(): ?RequestObject
    {
        $tokenName = $this->tokenService->getTokenName();
        
        if (!isset($_COOKIE[$tokenName])) {
            return null;
        }
        
        $token = $_COOKIE[$tokenName];
        
        try {
            if (!$this->tokenService->validateAndRefreshToken($token)) {
                return null;
            }
            
            $decodedToken = $this->tokenService->decodeJWT($token);
            $userId = $decodedToken->user_id;
            $userRole = $decodedToken->acctype;
            
            $userInfo = $this->retrieveUserInfo($userId, $userRole);
            
            return new RequestObject($userInfo);
        } catch (\Exception $e) {
            // Log the exception
            error_log("Authentication middleware error: " . $e->getMessage());
            return null;
        }
    }
}