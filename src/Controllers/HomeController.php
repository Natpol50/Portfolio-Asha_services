<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\PersonalInfoModel;
use App\Services\TranslationService;

/**
 * HomeController - Handles main site pages
 * 
 * This controller is responsible for rendering the main public-facing
 * pages of the website, including the home page and contact page.
 */
class HomeController extends BaseController
{
    private ProjectModel $projectModel;
    private PersonalInfoModel $personalInfoModel;
    
    /**
     * Create a new HomeController instance
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->personalInfoModel = new PersonalInfoModel();
    }
    
    /**
     * Display the home page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function index(RequestObject $request): void
    {
        // Set language to French for this page
        $langCode = 'fr';
        $_SESSION['language'] = $langCode;
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get current projects
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        
        // Get past projects
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Get personal info
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the home page
        echo $this->render('home/index', [
            'request' => $request,
            'currentProjects' => $currentProjects,
            'pastProjects' => $pastProjects,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display the English home page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function indexEn(RequestObject $request): void
    {
        // Set language to English for this page
        $langCode = 'en';
        $_SESSION['language'] = $langCode;
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get current projects
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        
        // Get past projects
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Get personal info
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the English home page
        echo $this->render('home/index_en', [
            'request' => $request,
            'currentProjects' => $currentProjects,
            'pastProjects' => $pastProjects,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display the contact page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contact(RequestObject $request): void
    {
        // Set language to French for this page
        $langCode = 'fr';
        $_SESSION['language'] = $langCode;
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the contact page
        echo $this->render('home/contact', [
            'request' => $request,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display the English contact page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contactEn(RequestObject $request): void
    {
        // Set language to English for this page
        $langCode = 'en';
        $_SESSION['language'] = $langCode;
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the English contact page
        echo $this->render('home/contact_en', [
            'request' => $request,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Process contact form submission
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function submitContact(RequestObject $request): void
    {
        // Get form data
        $subject = $request->getPost('subject', '');
        $message = $request->getPost('message', '');
        $email = $request->getPost('email', '');
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Simple validation
        $errors = [];
        
        if (empty($subject)) {
            $errors[] = $translationService->translate('contact.error.subject_required');
        }
        
        if (empty($message)) {
            $errors[] = $translationService->translate('contact.error.message_required');
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $translationService->translate('contact.error.invalid_email');
        }
        
        // If there are errors, redisplay the form with error messages
        if (!empty($errors)) {
            // Determine which template to use based on language
            $template = ($langCode === 'en') ? 'home/contact_en' : 'home/contact';
            
            echo $this->render($template, [
                'request' => $request,
                'personalInfo' => $personalInfo,
                'translations' => $translationService,
                'language' => $langCode,
                'error' => $errors,
                'formData' => [
                    'subject' => $subject,
                    'message' => $message,
                    'email' => $email
                ]
            ]);
            return;
        }
        
        // In a real application, this would send an email
        // For this example, we'll just show a success message
        
        // Set success message in session
        $_SESSION['success'] = [
            $translationService->translate('contact.success')
        ];
        
        // Redirect back to the appropriate contact page
        $redirectUrl = ($langCode === 'en') ? '/contact-en' : '/contact';
        header("Location: $redirectUrl");
        exit;
    }
}
