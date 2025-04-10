<?php

namespace App\Models;

use App\Services\Database;

/**
 * LanguageModel - Language data model
 * 
 * This class handles database operations related to languages,
 * including retrieval of available languages and translations.
 */
class LanguageModel
{
    private Database $database;
    
    /**
     * Create a new LanguageModel instance
     * 
     * @param Database|null $database Database service
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }
    
    /**
     * Get all active languages
     * 
     * @return array Active languages
     */
    public function getActiveLanguages(): array
    {
        $sql = 'SELECT * FROM languages WHERE is_active = 1 ORDER BY code';
        
        return $this->database->fetchAll($sql);
    }
    
    /**
     * Get a language by code
     * 
     * @param string $code Language code
     * @return object|null Language object or null if not found
     */
    public function getLanguageByCode(string $code): ?object
    {
        $sql = 'SELECT * FROM languages WHERE code = :code';
        
        return $this->database->fetchOne($sql, ['code' => $code]);
    }
    
    /**
     * Get a language by ID
     * 
     * @param int $id Language ID
     * @return object|null Language object or null if not found
     */
    public function getLanguageById(int $id): ?object
    {
        $sql = 'SELECT * FROM languages WHERE id = :id';
        
        return $this->database->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Get UI texts for a specific language
     * 
     * @param string $langCode Language code
     * @return array Associative array of text keys and translations
     */
    public function getUiTexts(string $langCode): array
    {
        // Get language ID
        $language = $this->getLanguageByCode($langCode);
        
        if (!$language) {
            return [];
        }
        
        $sql = 'SELECT ut.text_key, utt.text
                FROM ui_texts ut
                JOIN ui_text_translations utt ON ut.id = utt.ui_text_id
                WHERE utt.language_id = :langId';
        
        $translations = $this->database->fetchAll($sql, ['langId' => $language->id]);
        
        // Convert to associative array
        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->text_key] = $translation->text;
        }
        
        return $result;
    }
    
    /**
     * Add or update a UI text
     * 
     * @param string $key Text key
     * @param array $translations Associative array of language code => translation
     * @return bool True if successful
     */
    public function updateUiText(string $key, array $translations): bool
    {
        try {
            $this->database->beginTransaction();
            
            // Get or create text entry
            $sql = 'SELECT id FROM ui_texts WHERE text_key = :key';
            $textEntry = $this->database->fetchOne($sql, ['key' => $key]);
            
            $textId = null;
            if ($textEntry) {
                $textId = $textEntry->id;
            } else {
                // Create new text entry
                $textId = $this->database->insert('ui_texts', [
                    'text_key' => $key,
                    'context' => null
                ]);
            }
            
            // Update translations
            foreach ($translations as $langCode => $translation) {
                $language = $this->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                // Check if translation exists
                $sql = 'SELECT id FROM ui_text_translations 
                        WHERE ui_text_id = :textId AND language_id = :langId';
                $translationEntry = $this->database->fetchOne($sql, [
                    'textId' => $textId,
                    'langId' => $language->id
                ]);
                
                if ($translationEntry) {
                    // Update translation
                    $this->database->update(
                        'ui_text_translations',
                        ['text' => $translation],
                        'id = :id',
                        ['id' => $translationEntry->id]
                    );
                } else {
                    // Create translation
                    $this->database->insert('ui_text_translations', [
                        'ui_text_id' => $textId,
                        'language_id' => $language->id,
                        'text' => $translation
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
