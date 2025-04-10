<?php

namespace App\Services;

use App\Config\ConfigInterface;
use App\Exceptions\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * TokenService - JWT token management service
 * 
 * This class handles the creation, validation, and refreshing of JWT tokens
 * for authentication purposes.
 */
class TokenService
{
    private ConfigInterface $config;
    
    /**
     * Create a new TokenService instance
     * 
     * @param ConfigInterface $config Configuration access object
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get the JWT token name from configuration
     * 
     * @return string Token name
     */
    public function getTokenName(): string
    {
        return $this->config->get('JWT_NAME', 'portfolio_token');
    }
    
    /**
     * Create a new JWT token for a user
     * 
     * @param int $userId User ID
     * @param int $accType Account type/role (default: 1)
     * @return string JWT token
     */
    public function createJWT(int $userId, int $accType = 1): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + (int) $this->config->get('JWT_EXPIRY', 1800); // Default: 30 minutes
        
        $payload = [
            'iat' => $issuedAt,           // Issued at: time when the token was generated
            'exp' => $expiresAt,          // Expires at: expiration time
            'user_id' => $userId,         // User ID
            'acctype' => $accType,        // Account type/role
            'jti' => uniqid()             // JWT ID: unique identifier for the token
        ];
        
        $token = JWT::encode(
            $payload,
            $this->config->get('JWT_SECRET'),
            'HS256'
        );
        
        // Store the token in a cookie
        $this->setTokenCookie($token);
        
        return $token;
    }
    
    /**
     * Create a refresh token for a user
     * 
     * @param int $userId User ID
     * @return string Refresh token
     */
    public function createRefreshToken(int $userId): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + (int) $this->config->get('JWT_REFRESH_EXPIRY', 604800); // Default: 7 days
        
        $payload = [
            'iat' => $issuedAt,           // Issued at
            'exp' => $expiresAt,          // Expires at
            'user_id' => $userId,         // User ID
            'type' => 'refresh',          // Token type
            'jti' => uniqid()             // JWT ID
        ];
        
        $token = JWT::encode(
            $payload,
            $this->config->get('JWT_SECRET'),
            'HS256'
        );
        
        // Store the refresh token in a cookie
        $this->setRefreshTokenCookie($token);
        
        return $token;
    }
    
    /**
     * Validate and refresh a JWT token if needed
     * 
     * @param string $token JWT token
     * @return bool True if the token is valid
     */
    public function validateAndRefreshToken(string $token): bool
    {
        try {
            $payload = $this->decodeJWT($token);
            
            // Check if token is about to expire (less than half of its lifetime remaining)
            $expiresAt = $payload->exp;
            $issuedAt = $payload->iat;
            $halfLifetime = ($expiresAt - $issuedAt) / 2;
            
            if (time() > $issuedAt + $halfLifetime) {
                // Token is in the second half of its lifetime, refresh it
                $this->createJWT($payload->user_id, $payload->acctype);
            }
            
            return true;
        } catch (\Exception $e) {
            // Token is invalid or expired
            return false;
        }
    }
    
    /**
     * Decode a JWT token
     * 
     * @param string $token JWT token
     * @return object Decoded token payload
     * @throws AuthenticationException If token is invalid
     */
    public function decodeJWT(string $token): object
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->config->get('JWT_SECRET'), 'HS256')
            );
            
            return $decoded;
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Set the token cookie
     * 
     * @param string $token JWT token
     * @return void
     */
    private function setTokenCookie(string $token): void
    {
        $expiry = time() + (int) $this->config->get('JWT_EXPIRY', 1800);
        
        setcookie(
            $this->getTokenName(),
            $token,
            [
                'expires' => $expiry,
                'path' => '/',
                'domain' => '',
                'secure' => $_SERVER['HTTPS'] ?? false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Set the refresh token cookie
     * 
     * @param string $token Refresh token
     * @return void
     */
    private function setRefreshTokenCookie(string $token): void
    {
        $expiry = time() + (int) $this->config->get('JWT_REFRESH_EXPIRY', 604800);
        
        setcookie(
            $this->getTokenName() . '_refresh',
            $token,
            [
                'expires' => $expiry,
                'path' => '/',
                'domain' => '',
                'secure' => $_SERVER['HTTPS'] ?? false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Clear all authentication cookies (logout)
     * 
     * @return void
     */
    public function logout(): void
    {
        // Clear the main token cookie
        setcookie(
            $this->getTokenName(),
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $_SERVER['HTTPS'] ?? false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
        
        // Clear the refresh token cookie
        setcookie(
            $this->getTokenName() . '_refresh',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $_SERVER['HTTPS'] ?? false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
        
        // Also clear the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
