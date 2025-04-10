<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\PersonalInfoModel;
use App\Models\LanguageModel;
use App\Services\TranslationService;
use App\Services\ValidationService;

/**
 * AdminController - Handles administrative actions
 * 
 * This controller is responsible for rendering and processing
 * administrative pages, including project and personal info management.
 */
class AdminController extends BaseController
{
    private ProjectModel $projectModel;
    private PersonalInfoModel $personalInfoModel;
    private LanguageModel $languageModel;
    
    /**
     * Create a new AdminController instance
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->personalInfoModel = new PersonalInfoModel();
        $this->languageModel = new LanguageModel();
    }
    
    /**
     * Display the admin dashboard
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function dashboard(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project counts
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Render the dashboard
        echo $this->render('admin/dashboard', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'currentProjectCount' => count($currentProjects),
            'pastProjectCount' => count($pastProjects)
        ]);
    }
    
    /**
     * Display the projects list
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function projects(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get all projects
        $projects = $this->projectModel->getAllProjects($langCode);
        
        // Render the projects list
        echo $this->render('admin/projects', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'projects' => $projects
        ]);
    }
    
    /**
     * Display the new project form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function newProject(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();
        
        // Render the new project form
        echo $this->render('admin/project-form', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'languages' => $languages,
            'project' => null,
            'projectTranslations' => [],
            'formData' => [],
            'isNew' => true
        ]);
    }
    
    /**
     * Process new project creation
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function createProject(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get form data
        $type = $request->getPost('type', '');
        $status = $request->getPost('status', '');
        $startDate = $request->getPost('start_date', '');
        $endDate = $request->getPost('end_date', '');
        $githubUrl = $request->getPost('github_url', '');
        $websiteUrl = $request->getPost('website_url', '');
        
        // Get translations for each language
        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        
        foreach ($languages as $language) {
            $translations[$language->code] = [
                'title' => $request->getPost("title_{$language->code}", ''),
                'subtitle' => $request->getPost("subtitle_{$language->code}", ''),
                'description' => $request->getPost("description_{$language->code}", ''),
                'skills' => $request->getPost("skills_{$language->code}", '')
            ];
        }
        
        // Validate form data
        $validator = new ValidationService();
        
        $validator->required($type, 'type', $translationService->translate('project.error.type_required'));
        $validator->required($status, 'status', $translationService->translate('project.error.status_required'));
        $validator->required($startDate, 'start_date', $translationService->translate('project.error.start_date_required'));
        $validator->date($startDate, 'start_date', $translationService->translate('project.error.invalid_start_date'));
        
        if (!empty($endDate)) {
            $validator->date($endDate, 'end_date', $translationService->translate('project.error.invalid_end_date'));
            
            if ($startDate > $endDate) {
                $validator->addError('end_date', $translationService->translate('project.error.end_date_before_start'));
            }
        }
        
        if (!empty($githubUrl)) {
            $validator->url($githubUrl, 'github_url', $translationService->translate('project.error.invalid_github_url'));
        }
        
        if (!empty($websiteUrl)) {
            $validator->url($websiteUrl, 'website_url', $translationService->translate('project.error.invalid_website_url'));
        }
        
        // Validate translations
        foreach ($languages as $language) {
            $validator->required(
                $translations[$language->code]['title'],
                "title_{$language->code}",
                $translationService->translate('project.error.title_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['description'],
                "description_{$language->code}",
                $translationService->translate('project.error.description_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['skills'],
                "skills_{$language->code}",
                $translationService->translate('project.error.skills_required', ['language' => $language->name])
            );
        }
        
        // If there are validation errors, redisplay the form
        if ($validator->hasErrors()) {
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Render the form with errors
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'project' => null,
                'projectTranslations' => [],
                'formData' => $formData,
                'isNew' => true,
                'error' => $validator->getErrors()
            ]);
            return;
        }
        
        // Create project data
        $projectData = [
            'type' => $type,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'github_url' => $githubUrl ?: null,
            'website_url' => $websiteUrl ?: null
        ];
        
        try {
            // Create the project
            $projectId = $this->projectModel->createProject($projectData, $translations);
            
            // Set success message
            $_SESSION['success'] = [
                $translationService->translate('project.success.created')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating project: ' . $e->getMessage());
            
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Render the form with error
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'project' => null,
                'projectTranslations' => [],
                'formData' => $formData,
                'isNew' => true,
                'error' => [$translationService->translate('project.error.create_failed')]
            ]);
        }
    }
    
    /**
     * Display the edit project form
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function editProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project
        $project = $this->projectModel->getProjectById($id, $langCode);
        
        if (!$project) {
            // Project not found
            $_SESSION['error'] = [
                $translationService->translate('project.error.not_found')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        }
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();
        
        // Get project translations
        $projectTranslations = $this->projectModel->getProjectTranslations($id);
        
        // Render the edit project form
        echo $this->render('admin/project-form', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'languages' => $languages,
            'project' => $project,
            'projectTranslations' => $projectTranslations,
            'formData' => [],
            'isNew' => false
        ]);
    }
    
    /**
     * Process project update
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function updateProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project
        $project = $this->projectModel->getProjectById($id, $langCode);
        
        if (!$project) {
            // Project not found
            $_SESSION['error'] = [
                $translationService->translate('project.error.not_found')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        }
        
        // Get form data
        $type = $request->getPost('type', '');
        $status = $request->getPost('status', '');
        $startDate = $request->getPost('start_date', '');
        $endDate = $request->getPost('end_date', '');
        $githubUrl = $request->getPost('github_url', '');
        $websiteUrl = $request->getPost('website_url', '');
        
        // Get translations for each language
        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        
        foreach ($languages as $language) {
            $translations[$language->code] = [
                'title' => $request->getPost("title_{$language->code}", ''),
                'subtitle' => $request->getPost("subtitle_{$language->code}", ''),
                'description' => $request->getPost("description_{$language->code}", ''),
                'skills' => $request->getPost("skills_{$language->code}", '')
            ];
        }
        
        // Validate form data
        $validator = new ValidationService();
        
        $validator->required($type, 'type', $translationService->translate('project.error.type_required'));
        $validator->required($status, 'status', $translationService->translate('project.error.status_required'));
        $validator->required($startDate, 'start_date', $translationService->translate('project.error.start_date_required'));
        $validator->date($startDate, 'start_date', $translationService->translate('project.error.invalid_start_date'));
        
        if (!empty($endDate)) {
            $validator->date($endDate, 'end_date', $translationService->translate('project.error.invalid_end_date'));
            
            if ($startDate > $endDate) {
                $validator->addError('end_date', $translationService->translate('project.error.end_date_before_start'));
            }
        }
        
        if (!empty($githubUrl)) {
            $validator->url($githubUrl, 'github_url', $translationService->translate('project.error.invalid_github_url'));
        }
        
        if (!empty($websiteUrl)) {
            $validator->url($websiteUrl, 'website_url', $translationService->translate('project.error.invalid_website_url'));
        }
        
        // Validate translations
        foreach ($languages as $language) {
            $validator->required(
                $translations[$language->code]['title'],
                "title_{$language->code}",
                $translationService->translate('project.error.title_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['description'],
                "description_{$language->code}",
                $translationService->translate('project.error.description_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['skills'],
                "skills_{$language->code}",
                $translationService->translate('project.error.skills_required', ['language' => $language->name])
            );
        }
        
        // If there are validation errors, redisplay the form
        if ($validator->hasErrors()) {
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Get project translations
            $projectTranslations = $this->projectModel->getProjectTranslations($id);
            
            // Render the form with errors
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'project' => $project,
                'projectTranslations' => $projectTranslations,
                'formData' => $formData,
                'isNew' => false,
                'error' => $validator->getErrors()
            ]);
            return;
        }
        
        // Update project data
        $projectData = [
            'type' => $type,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'github_url' => $githubUrl ?: null,
            'website_url' => $websiteUrl ?: null
        ];
        
        try {
            // Update the project
            $this->projectModel->updateProject($id, $projectData, $translations);
            
            // Set success message
            $_SESSION['success'] = [
                $translationService->translate('project.success.updated')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error updating project: ' . $e->getMessage());
            
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Get project translations
            $projectTranslations = $this->projectModel->getProjectTranslations($id);
            
            // Render the form with error
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'project' => $project,
                'projectTranslations' => $projectTranslations,
                'formData' => $formData,
                'isNew' => false,
                'error' => [$translationService->translate('project.error.update_failed')]
            ]);
        }
    }
    
    /**
     * Process project deletion
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function deleteProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        try {
            // Check if project exists
            $project = $this->projectModel->getProjectById($id, $langCode);
            
            if (!$project) {
                // Project not found
                $_SESSION['error'] = [
                    $translationService->translate('project.error.not_found')
                ];
                
                // Redirect to projects list
                header('Location: /admin/projects');
                exit;
            }
            
            // Delete the project
            $this->projectModel->deleteProject($id);
            
            // Set success message
            $_SESSION['success'] = [
                $translationService->translate('project.success.deleted')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error deleting project: ' . $e->getMessage());
            
            // Set error message
            $_SESSION['error'] = [
                $translationService->translate('project.error.delete_failed')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        }
    }
    
    /**
     * Redirect to login page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    private function redirectToLogin(RequestObject $request): void
    {
        // Save current URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: /login');
        exit;
    }
}
