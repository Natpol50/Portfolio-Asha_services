<?php

namespace App\Models;

use App\Services\Database;

/**
 * PersonalInfoModel - Personal information data model
 * 
 * This class handles database operations related to personal information,
 * including multilingual "about me" content.
 */
class PersonalInfoModel
{
    private Database $database;
    
    /**
     * Create a new PersonalInfoModel instance
     * 
     * @param Database|null $database Database service
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }
    
    /**
     * Get personal information with translations for a specific language
     * 
     * @param string $langCode Language code
     * @return object|null Personal information with translations or null if not found
     */
    public function getPersonalInfo(string $langCode): ?object
    {
        // Get language ID
        $languageModel = new LanguageModel($this->database);
        $language = $languageModel->getLanguageByCode($langCode);
        
        if (!$language) {
            return null;
        }
        
        $sql = 'SELECT pi.*, pit.about_text
                FROM personal_info pi
                LEFT JOIN personal_info_translations pit ON pi.id = pit.personal_info_id AND pit.language_id = :langId
                LIMIT 1';
        
        return $this->database->fetchOne($sql, ['langId' => $language->id]);
    }
    
    /**
     * Get all translations for personal information
     * 
     * @param int $id Personal info ID
     * @return array Associative array of language code => translation
     */
    public function getPersonalInfoTranslations(int $id): array
    {
        $sql = 'SELECT l.code, pit.about_text
                FROM personal_info_translations pit
                JOIN languages l ON pit.language_id = l.id
                WHERE pit.personal_info_id = :id';
        
        $translations = $this->database->fetchAll($sql, ['id' => $id]);
        
        // Convert to associative array
        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->code] = [
                'about_text' => $translation->about_text
            ];
        }
        
        return $result;
    }
    
    /**
     * Update personal information with translations
     * 
     * @param array $personalData Personal information data
     * @param array $translations Associative array of language code => translation data
     * @return bool True if successful
     */
    public function updatePersonalInfo(array $personalData, array $translations): bool
    {
        try {
            $this->database->beginTransaction();
            
            // Get or create personal info
            $personalInfo = $this->database->fetchOne('SELECT * FROM personal_info LIMIT 1');
            
            $personalInfoId = null;
            if ($personalInfo) {
                $personalInfoId = $personalInfo->id;
                
                // Update existing personal info
                $this->database->update(
                    'personal_info',
                    [
                        'email' => $personalData['email'],
                        'github_url' => $personalData['github_url'],
                        'linkedin_url' => $personalData['linkedin_url'],
                        'discord_url' => $personalData['discord_url'] ?? null,
                        'profile_picture_url' => $personalData['profile_picture_url']
                    ],
                    'id = :id',
                    ['id' => $personalInfoId]
                );
            } else {
                // Create new personal info
                $personalInfoId = $this->database->insert('personal_info', [
                    'email' => $personalData['email'],
                    'github_url' => $personalData['github_url'],
                    'linkedin_url' => $personalData['linkedin_url'],
                    'discord_url' => $personalData['discord_url'] ?? null,
                    'profile_picture_url' => $personalData['profile_picture_url']
                ]);
            }
            
            // Update translations
            $languageModel = new LanguageModel($this->database);
            
            foreach ($translations as $langCode => $translationData) {
                $language = $languageModel->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                // Check if translation exists
                $sql = 'SELECT id FROM personal_info_translations 
                        WHERE personal_info_id = :personalInfoId AND language_id = :langId';
                $existingTranslation = $this->database->fetchOne($sql, [
                    'personalInfoId' => $personalInfoId,
                    'langId' => $language->id
                ]);
                
                if ($existingTranslation) {
                    // Update existing translation
                    $this->database->update(
                        'personal_info_translations',
                        ['about_text' => $translationData['about_text']],
                        'id = :id',
                        ['id' => $existingTranslation->id]
                    );
                } else {
                    // Create new translation
                    $this->database->insert('personal_info_translations', [
                        'personal_info_id' => $personalInfoId,
                        'language_id' => $language->id,
                        'about_text' => $translationData['about_text']
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
}
