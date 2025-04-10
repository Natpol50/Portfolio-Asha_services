<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\Database;
use App\Services\TokenService;
use App\Models\UserModel;
use App\Core\RequestObject;
use App\Exceptions\AuthenticationException;

/**
 * AuthController - Handles authentication actions
 */
class AuthController extends BaseController
{
    private UserModel $userModel;
    private TokenService $tokenService;
    
    /**
     * Create a new AuthController instance
     */
    public function __construct()
    {
        // Initialize dependencies
        $database = new Database();
        
        $this->userModel = new UserModel($database);
        
        // Create config for token service
        $configManager = \App\Config\ConfigManager::getInstance();
        $tokenConfig = $configManager->getConfigFor(new TokenService());
        
        $this->tokenService = new TokenService($tokenConfig);
    }
    
    /**
     * Display login form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function loginForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Render login form with empty arrays for messages
        echo $this->render('auth/login', [
            'error' => [],
            'success' => [],
            'request' => $request,
            'formData' => [] // Empty form data
        ]);
    }
    
    /**
     * Process login attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function login(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Get login credentials
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Save form data to repopulate the form if there's an error
        $formData = [
            'email' => $email,
            'remember_me' => $rememberMe
        ];
        
        $errorMessages = [];
        $successMessages = [];
        
        try {
            // Validate credentials
            $user = $this->userModel->verifyCredentials($email, $password);
            
            if (!$user) {
                // Add error message to array
                $errorMessages[] = 'Email ou mot de passe incorrect.';
                
                // Render the login form with error and preserved form data
                echo $this->render('auth/login', [
                    'error' => $errorMessages,
                    'success' => $successMessages,
                    'request' => $request,
                    'formData' => $formData
                ]);
                exit; // Important to prevent further execution
            }
            
            // Create JWT token
            $token = $this->tokenService->createJWT($user->userId);
            
            // Create refresh token if "remember me" is checked
            if ($rememberMe) {
                $this->tokenService->createRefreshToken($user->userId);
            }
            
            // Redirect to home page
            header('Location: /');
            exit;
        } catch (AuthenticationException $e) {
            // Log the error
            error_log('Authentication error: ' . $e->getMessage());
            
            // Add error to array
            $errorMessages[] = 'Une erreur est survenue lors de la connexion. Veuillez réessayer.';
            
            // Render the login form with error and preserved form data
            echo $this->render('auth/login', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'formData' => $formData
            ]);
            exit;
        }
    }
    
    /**
     * Process logout
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function logout(RequestObject $request): void
    {
        // Clear JWT token cookie
        $this->tokenService->logout();
        
        // Set up success message for login form
        $successMessages = ['Vous avez été déconnecté avec succès.'];
        
        // Render login form with success message
        echo $this->render('auth/login', [
            'error' => [],
            'success' => $successMessages,
            'request' => $request,
            'formData' => []
        ]);
        exit;
    }

    /**
     * Display registration form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function registerForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Render registration form with empty arrays
        echo $this->render('auth/register', [
            'error' => [],
            'success' => [],
            'request' => $request,
            'formData' => []
        ]);
    }
    
    /**
     * Process registration attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function register(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Get registration data
        $civilite = $_POST['civilite'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $userType = $_POST['userType'] ?? 'etudiant'; // Default to student
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        
        // Save form data to repopulate the form if there's an error
        $formData = [
            'civilite' => $civilite,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'email' => $email,
            'phone' => $phone,
            'userType' => $userType
        ];
        
        $errorMessages = [];
        $successMessages = [];
        
        // Validate password match
        if ($password !== $confirmPassword) {
            $errorMessages[] = 'Les mots de passe ne correspondent pas.';
            
            echo $this->render('auth/register', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'formData' => $formData
            ]);
            exit;
        }
        
        try {
            // Check if email already exists
            $existingUser = $this->userModel->getUserByEmail($email);
            if ($existingUser) {
                $errorMessages[] = 'Cet email est déjà utilisé.';
                
                echo $this->render('auth/register', [
                    'error' => $errorMessages,
                    'success' => $successMessages,
                    'request' => $request,
                    'formData' => $formData
                ]);
                exit;
            }
            
            // TODO : ALL USERS ROLEID authorized by everyone
            // Determine role ID based on user type
            $roleId = 5; // Default to basic user (5)
            if ($userType === 'tuteur') {
                $roleId = 3; // Pilote de promotion (3)
            } else if ($userType === 'basic') {
                $roleId = 5; // Admin user (5)
            } else if ($userType === 'etudiant') {
                $roleId = 4; // Student (4)
            }
            
            // Determine gender based on civilite
            $gender = 'N'; // Default to Not specified
            if ($civilite === 'Monsieur') {
                $gender = 'M';
            } else if ($civilite === 'Madame') {
                $gender = 'F';
            }
            
            // Create user data array
            $userData = [
                'userName' => $lastName,
                'userFirstName' => $firstName,
                'userEmail' => $email,
                'userPassword' => $password,
                'userPhone' => $phone,
                'userGender' => $gender,
                'userRoleId' => $roleId,
            ];
            
            // Create user
            $newUser = $this->userModel->createUser($userData);
            
            // Create JWT token for auto-login
            $token = $this->tokenService->createJWT($newUser->userId);
            
            // Redirect to home page with success message
            $_SESSION['success'] = ['Compte créé avec succès. Vous êtes maintenant connecté.'];
            header('Location: /');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Registration error: ' . $e->getMessage());
            
            // Add error message to array
            $errorMessages[] = 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.';
            
            // Render registration form with error and preserved form data
            echo $this->render('auth/register', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'formData' => $formData
            ]);
            exit;
        }
    }
    
    /**
     * Display forgot password form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forgotPasswordForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Render forgot password form with empty arrays
        echo $this->render('auth/forgot-password', [
            'error' => [],
            'success' => [],
            'request' => $request,
            'formData' => []
        ]);
    }
    
    /**
     * Process forgot password attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forgotPassword(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        $email = $_POST['email'] ?? '';
        
        // Save form data
        $formData = [
            'email' => $email
        ];
        
        // Initialize message arrays
        $successMessages = [];
        $errorMessages = [];
        
        // Validate email exists
        $user = $this->userModel->getUserByEmail($email);
        // For security, don't indicate whether email exists or not
        $successMessages[] = 'Si un compte existe avec cet email, un code de réinitialisation sera envoyé.';

        if (!$user) {
            
            
            // Render forgot password form with success message
            echo $this->render('auth/reset-code', [
                'success' => $successMessages,
                'error' => $errorMessages,
                'request' => $request,
                'formData' => $formData
            ]);
        } else {
            try {
                // In a real implementation, we would generate a code and send it via email
                // For demo purposes, we'll use a hardcoded code: 123456
                
                // Redirect to the code verification page
                echo $this->render('auth/reset-code', [
                    'error' => $errorMessages,
                    'email' => $email,
                    'request' => $request
                ]);
            } catch (\Exception $e) {
                // Log the error
                error_log('Forgot password error: ' . $e->getMessage());
                
                // Add error message
                $errorMessages[] = 'Une erreur est survenue. Veuillez réessayer.';
                
                // Render forgot password form with error message
                echo $this->render('auth/forgot-password', [
                    'success' => $successMessages,
                    'error' => $errorMessages,
                    'request' => $request,
                    'formData' => $formData
                ]);
            }
        }
    }
    
    /**
     * Process code verification for password reset
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function verifyResetCode(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        $email = $_POST['email'] ?? '';
        $resetCode = $_POST['resetCode'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // For demo purposes, check if code is 123456
        if ($resetCode !== '123456') {
            $errorMessages[] = 'Code de réinitialisation invalide. Veuillez réessayer.';
            
            // Send back to code verification page
            echo $this->render('auth/reset-code', [
                'error' => $errorMessages,
                'email' => $email,
                'request' => $request
            ]);
            exit;
        }
        
        // Generate a token (in a real implementation, this would be more secure)
        $token = md5($email . time());
        
        // Render reset password form
        echo $this->render('auth/reset-password', [
            'token' => $token,
            'email' => $email,
            'error' => $errorMessages,
            'success' => $successMessages,
            'request' => $request
        ]);
    }
    
    /**
     * Display reset password form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function resetPasswordForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // Validate token (would require implementation)
        if (empty($token) || empty($email)) {
            $errorMessages[] = 'Lien de réinitialisation invalide ou expiré.';
            
            // Render forgot password form with error
            echo $this->render('auth/forgot-password', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'formData' => []
            ]);
            exit;
        }
        
        // Render reset password form
        echo $this->render('auth/reset-password', [
            'token' => $token,
            'email' => $email,
            'error' => $errorMessages,
            'success' => $successMessages,
            'request' => $request
        ]);
    }
    
    /**
     * Process reset password attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function resetPassword(RequestObject $request): void
    {
        // If user is already authenticated, redirect to home
        if ($request->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        
        // Get form data
        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // Validate token and email
        if (empty($token) || empty($email)) {
            $errorMessages[] = 'Données de réinitialisation invalides. (A FINIR D\'IMPLEMENTER)';
            
            // Render forgot password form with error
            echo $this->render('auth/forgot-password', [
                'error' => $errorMessages,
                'success' => [],
                'request' => $request,
                'formData' => []
            ]);
            exit;
        }
        
        // Validate passwords match
        if ($password !== $confirmPassword) {
            $errorMessages[] = 'Les mots de passe ne correspondent pas.';
            
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request
            ]);
            exit;
        }
        
        // Validate password length
        if (strlen($password) < 8) {
            $errorMessages[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request
            ]);
            exit;
        }
        
        try {
            // In a real application, we would verify the token and update the user's password
            // For demo purposes, we'll just show a success message
            
            // Get user by email
            $user = $this->userModel->getUserByEmail($email);
            
            if (!$user) {
                $errorMessages[] = 'Utilisateur non trouvé.';
                
                echo $this->render('auth/forgot-password', [
                    'error' => $errorMessages,
                    'success' => [],
                    'request' => $request,
                    'formData' => []
                ]);
                exit;
            }
            
            // In a real application, update the user's password here
            // For demo, just show success message
            
            // Set success message for login form
            $successMessages[] = 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.';
            
            // Render login form with success message
            echo $this->render('auth/login', [
                'error' => [],
                'success' => $successMessages,
                'request' => $request,
                'formData' => ['email' => $email]
            ]);
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Password reset error: ' . $e->getMessage());
            
            // Add error message
            $errorMessages[] = 'Une erreur est survenue lors de la réinitialisation du mot de passe. Veuillez réessayer.';
            
            // Return to reset password form
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => [],
                'request' => $request
            ]);
            exit;
        }
    }
    

}