<?php

namespace App\Models;

use App\Services\Database;

/**
 * ProjectModel - Project data model
 * 
 * This class handles database operations related to projects,
 * including multilingual project content.
 */
class ProjectModel
{
    private Database $database;
    
    /**
     * Create a new ProjectModel instance
     * 
     * @param Database|null $database Database service
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }
    
    /**
     * Get all projects with translations for a specific language
     * 
     * @param string $langCode Language code
     * @param string|null $status Filter by status (current, past, null for all)
     * @return array Projects with translations
     */
    public function getAllProjects(string $langCode, ?string $status = null): array
    {
        // Get language ID
        $languageModel = new LanguageModel($this->database);
        $language = $languageModel->getLanguageByCode($langCode);
        
        if (!$language) {
            return [];
        }
        
        $params = ['langId' => $language->id];
        
        $sql = 'SELECT p.*, pt.title, pt.subtitle, pt.description, pt.skills
                FROM projects p
                LEFT JOIN project_translations pt ON p.id = pt.project_id AND pt.language_id = :langId';
        
        if ($status) {
            $sql .= ' WHERE p.status = :status';
            $params['status'] = $status;
        }
        
        $sql .= ' ORDER BY p.start_date DESC';
        
        return $this->database->fetchAll($sql, $params);
    }
    
    /**
     * Get a project by ID with translations for a specific language
     * 
     * @param int $id Project ID
     * @param string $langCode Language code
     * @return object|null Project with translations or null if not found
     */
    public function getProjectById(int $id, string $langCode): ?object
    {
        // Get language ID
        $languageModel = new LanguageModel($this->database);
        $language = $languageModel->getLanguageByCode($langCode);
        
        if (!$language) {
            return null;
        }
        
        $sql = 'SELECT p.*, pt.title, pt.subtitle, pt.description, pt.skills
                FROM projects p
                LEFT JOIN project_translations pt ON p.id = pt.project_id AND pt.language_id = :langId
                WHERE p.id = :id';
        
        return $this->database->fetchOne($sql, [
            'id' => $id,
            'langId' => $language->id
        ]);
    }
    
    /**
     * Get all translations for a project
     * 
     * @param int $id Project ID
     * @return array Associative array of language code => translation
     */
    public function getProjectTranslations(int $id): array
    {
        $sql = 'SELECT l.code, pt.*
                FROM project_translations pt
                JOIN languages l ON pt.language_id = l.id
                WHERE pt.project_id = :id';
        
        $translations = $this->database->fetchAll($sql, ['id' => $id]);
        
        // Convert to associative array
        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->code] = [
                'title' => $translation->title,
                'subtitle' => $translation->subtitle,
                'description' => $translation->description,
                'skills' => $translation->skills
            ];
        }
        
        return $result;
    }
    
    /**
     * Create a new project with translations
     * 
     * @param array $projectData Project data
     * @param array $translations Associative array of language code => translation data
     * @return int New project ID
     */
    public function createProject(array $projectData, array $translations): int
    {
        try {
            $this->database->beginTransaction();
            
            // Insert project
            $projectId = $this->database->insert('projects', [
                'type' => $projectData['type'],
                'status' => $projectData['status'],
                'start_date' => $projectData['start_date'],
                'end_date' => $projectData['end_date'] ?? null,
                'github_url' => $projectData['github_url'] ?? null,
                'website_url' => $projectData['website_url'] ?? null
            ]);
            
            // Insert translations
            $languageModel = new LanguageModel($this->database);
            
            foreach ($translations as $langCode => $translationData) {
                $language = $languageModel->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                $this->database->insert('project_translations', [
                    'project_id' => $projectId,
                    'language_id' => $language->id,
                    'title' => $translationData['title'],
                    'subtitle' => $translationData['subtitle'] ?? null,
                    'description' => $translationData['description'],
                    'skills' => $translationData['skills']
                ]);
            }
            
            $this->database->commit();
            return $projectId;
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }
    
    /**
     * Update a project and its translations
     * 
     * @param int $id Project ID
     * @param array $projectData Project data
     * @param array $translations Associative array of language code => translation data
     * @return bool True if successful
     */
    public function updateProject(int $id, array $projectData, array $translations): bool
    {
        try {
            $this->database->beginTransaction();
            
            // Update project
            $this->database->update('projects',
                [
                    'type' => $projectData['type'],
                    'status' => $projectData['status'],
                    'start_date' => $projectData['start_date'],
                    'end_date' => $projectData['end_date'] ?? null,
                    'github_url' => $projectData['github_url'] ?? null,
                    'website_url' => $projectData['website_url'] ?? null
                ],
                'id = :id',
                ['id' => $id]
            );
            
            // Update translations
            $languageModel = new LanguageModel($this->database);
            
            foreach ($translations as $langCode => $translationData) {
                $language = $languageModel->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                // Check if translation exists
                $sql = 'SELECT id FROM project_translations 
                        WHERE project_id = :projectId AND language_id = :langId';
                $existingTranslation = $this->database->fetchOne($sql, [
                    'projectId' => $id,
                    'langId' => $language->id
                ]);
                
                if ($existingTranslation) {
                    // Update existing translation
                    $this->database->update(
                        'project_translations',
                        [
                            'title' => $translationData['title'],
                            'subtitle' => $translationData['subtitle'] ?? null,
                            'description' => $translationData['description'],
                            'skills' => $translationData['skills']
                        ],
                        'id = :id',
                        ['id' => $existingTranslation->id]
                    );
                } else {
                    // Create new translation
                    $this->database->insert('project_translations', [
                        'project_id' => $id,
                        'language_id' => $language->id,
                        'title' => $translationData['title'],
                        'subtitle' => $translationData['subtitle'] ?? null,
                        'description' => $translationData['description'],
                        'skills' => $translationData['skills']
                    ]);
                }
            }
            
            $this->database->commit();
            return true;
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }
    
    /**
     * Delete a project and its translations
     * 
     * @param int $id Project ID
     * @return bool True if successful
     */
    public function deleteProject(int $id): bool
    {
        try {
            // Project translations will be deleted automatically by foreign key constraints
            return $this->database->delete('projects', 'id = :id', ['id' => $id]) > 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
