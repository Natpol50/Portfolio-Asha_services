<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\LanguageModel;
use App\Services\TranslationService;

/**
 * ProjectController - Handles project display and filtering
 * 
 * This controller is responsible for displaying individual projects
 * and filtered lists of projects.
 */
class ProjectController extends BaseController
{
    private ProjectModel $projectModel;
    private LanguageModel $languageModel;
    
    /**
     * Create a new ProjectController instance
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->languageModel = new LanguageModel();
    }
    
    /**
     * Display a single project
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function show(RequestObject $request, int $id): void
    {
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project
        $project = $this->projectModel->getProjectById($id, $langCode);
        
        if (!$project) {
            // Project not found, show 404 page
            $errorController = new ErrorController();
            $errorController->notFound($request);
            return;
        }
        
        // Render the project page
        echo $this->render('projects/show', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'project' => $project
        ]);
    }
    
    /**
     * Display list of projects filtered by type
     * 
     * @param RequestObject $request Current request information
     * @param string|null $type Project type to filter by
     * @return void
     */
    public function byType(RequestObject $request, ?string $type = null): void
    {
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get all projects
        $allProjects = $this->projectModel->getAllProjects($langCode);
        
        // Filter projects by type if specified
        $projects = [];
        if ($type) {
            foreach ($allProjects as $project) {
                if ($project->type === $type) {
                    $projects[] = $project;
                }
            }
        } else {
            $projects = $allProjects;
        }
        
        // Get unique project types for filter options
        $types = [];
        foreach ($allProjects as $project) {
            if (!in_array($project->type, $types)) {
                $types[] = $project->type;
            }
        }
        
        // Render the projects list
        echo $this->render('projects/list', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'projects' => $projects,
            'types' => $types,
            'currentType' => $type
        ]);
    }
    
    /**
     * Display list of projects filtered by status
     * 
     * @param RequestObject $request Current request information
     * @param string $status Project status to filter by (current, past)
     * @return void
     */
    public function byStatus(RequestObject $request, string $status): void
    {
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get projects filtered by status
        $projects = $this->projectModel->getAllProjects($langCode, $status);
        
        // Render the projects list
        echo $this->render('projects/list', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'projects' => $projects,
            'status' => $status
        ]);
    }
    
    /**
     * Display list of all projects with search/filter capabilities
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function index(RequestObject $request): void
    {
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get search/filter parameters
        $type = $request->getQuery('type');
        $status = $request->getQuery('status');
        $search = $request->getQuery('search');
        
        // Get all projects
        $allProjects = $this->projectModel->getAllProjects($langCode);
        
        // Apply filters
        $projects = $allProjects;
        
        // Filter by type if specified
        if ($type) {
            $filtered = [];
            foreach ($projects as $project) {
                if ($project->type === $type) {
                    $filtered[] = $project;
                }
            }
            $projects = $filtered;
        }
        
        // Filter by status if specified
        if ($status) {
            $filtered = [];
            foreach ($projects as $project) {
                if ($project->status === $status) {
                    $filtered[] = $project;
                }
            }
            $projects = $filtered;
        }
        
        // Filter by search term if specified
        if ($search) {
            $filtered = [];
            foreach ($projects as $project) {
                if (
                    stripos($project->title, $search) !== false ||
                    stripos($project->description, $search) !== false ||
                    stripos($project->skills, $search) !== false
                ) {
                    $filtered[] = $project;
                }
            }
            $projects = $filtered;
        }
        
        // Get unique project types for filter options
        $types = [];
        foreach ($allProjects as $project) {
            if (!in_array($project->type, $types)) {
                $types[] = $project->type;
            }
        }
        
        // Render the projects list
        echo $this->render('projects/index', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'projects' => $projects,
            'types' => $types,
            'statuses' => ['current', 'past', 'canceled'],
            'currentType' => $type,
            'currentStatus' => $status,
            'searchTerm' => $search
        ]);
    }
}
