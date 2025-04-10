<?php

namespace App\Models;

use App\Services\Database;
use App\Exceptions\AuthenticationException;

/**
 * UserModel - User data model
 * 
 * This class handles database operations related to users,
 * including authentication and user management.
 */
class UserModel
{
    private Database $database;
    
    /**
     * Create a new UserModel instance
     * 
     * @param Database|null $database Database service
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }
    
    /**
     * Get a user by ID
     * 
     * @param int $userId User ID
     * @return object|null User object or null if not found
     */
    public function getUserById(int $userId): ?object
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        
        return $this->database->fetchOne($sql, ['id' => $userId]);
    }
    
    /**
     * Get a user by email
     * 
     * @param string $email User email
     * @return object|null User object or null if not found
     */
    public function getUserByEmail(string $email): ?object
    {
        $sql = 'SELECT * FROM users WHERE email = :email';
        
        return $this->database->fetchOne($sql, ['email' => $email]);
    }
    
    /**
     * Verify user credentials (email and password)
     * 
     * @param string $email User email
     * @param string $password User password (plaintext)
     * @return object|false User object or false if invalid credentials
     * @throws AuthenticationException If an error occurs during authentication
     */
    public function verifyCredentials(string $email, string $password)
    {
        try {
            // Get user by email
            $user = $this->getUserByEmail($email);
            
            // User not found
            if (!$user) {
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $user->password_hash)) {
                return false;
            }
            
            // Map database fields to expected fields for compatibility with existing code
            $mappedUser = (object)[
                'userId' => $user->id,
                'userName' => $user->last_name,
                'userFirstName' => $user->first_name,
                'userEmail' => $user->email,
                'profilePictureUrl' => '/assets/img/default-avatar.png'
            ];
            
            return $mappedUser;
        } catch (\Exception $e) {
            throw new AuthenticationException('Error during authentication: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @return object Created user
     * @throws \Exception If user creation fails
     */
    public function createUser(array $userData): object
    {
        // Start a database transaction
        $this->database->beginTransaction();
        
        try {
            // Map from legacy field names to new field names if needed
            $insertData = [
                'username' => $userData['userName'] ?? '',
                'email' => $userData['userEmail'] ?? '',
                'password_hash' => password_hash($userData['userPassword'] ?? '', PASSWORD_DEFAULT),
                'first_name' => $userData['userFirstName'] ?? '',
                'last_name' => $userData['userName'] ?? ''
            ];
            
            // Insert user into database
            $userId = $this->database->insert('users', $insertData);
            
            // Commit the transaction
            $this->database->commit();
            
            // Return the newly created user
            $user = $this->getUserById($userId);
            
            // Map database fields to expected fields for compatibility with existing code
            return (object)[
                'userId' => $user->id,
                'userName' => $user->last_name,
                'userFirstName' => $user->first_name,
                'userEmail' => $user->email,
                'profilePictureUrl' => '/assets/img/default-avatar.png'
            ];
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            $this->database->rollback();
            throw $e;
        }
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $userData User data to update
     * @return bool True if successful
     * @throws \Exception If update fails
     */
    public function updateUser(int $userId, array $userData): bool
    {
        try {
            $updateData = [];
            
            // Only include fields that are provided
            if (isset($userData['userName'])) {
                $updateData['last_name'] = $userData['userName'];
            }
            
            if (isset($userData['userFirstName'])) {
                $updateData['first_name'] = $userData['userFirstName'];
            }
            
            if (isset($userData['userEmail'])) {
                $updateData['email'] = $userData['userEmail'];
            }
            
            if (isset($userData['userPassword'])) {
                $updateData['password_hash'] = password_hash($userData['userPassword'], PASSWORD_DEFAULT);
            }
            
            // Only update if there are changes
            if (empty($updateData)) {
                return true;
            }
            
            // Update the user in the database
            $rowsAffected = $this->database->update('users', $updateData, 'id = :id', ['id' => $userId]);
            
            return $rowsAffected > 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $userId User ID
     * @return bool True if successful
     * @throws \Exception If deletion fails
     */
    public function deleteUser(int $userId): bool
    {
        try {
            // Delete the user from the database
            $rowsAffected = $this->database->delete('users', 'id = :id', ['id' => $userId]);
            
            return $rowsAffected > 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Change a user's password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool True if successful
     * @throws AuthenticationException If current password is incorrect
     * @throws \Exception If update fails
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            // Get user
            $user = $this->getUserById($userId);
            
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user->password_hash)) {
                throw new AuthenticationException('Current password is incorrect');
            }
            
            // Update password
            return $this->updateUser($userId, ['userPassword' => $newPassword]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
