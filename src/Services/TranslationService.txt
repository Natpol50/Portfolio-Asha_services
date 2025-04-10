<?php

namespace App\Services;

use App\Models\LanguageModel;

/**
 * TranslationService - Handles text translations
 * 
 * This service provides translation functionality for static texts,
 * supporting multiple languages and caching for performance.
 */
class TranslationService
{
    private LanguageModel $languageModel;
    private CacheService $cacheService;
    private array $translations = [];
    private string $currentLanguage;
    
    /**
     * Create a new TranslationService instance
     * 
     * @param string $language Language code
     * @param LanguageModel|null $languageModel Language model
     * @param CacheService|null $cacheService Cache service
     */
    public function __construct(string $language, ?LanguageModel $languageModel = null, ?CacheService $cacheService = null)
    {
        $this->currentLanguage = $language;
        $this->languageModel = $languageModel ?? new LanguageModel();
        $this->cacheService = $cacheService ?? new CacheService();
        
        // Load translations for the current language
        $this->loadTranslations($language);
    }
    
    /**
     * Translate a key
     * 
     * @param string $key Translation key
     * @param array $params Parameters to replace in the translation
     * @return string Translated string or key if translation not found
     */
    public function translate(string $key, array $params = []): string
    {
        // Get translation
        $translation = $this->translations[$key] ?? $key;
        
        // Replace parameters
        foreach ($params as $name => $value) {
            $translation = str_replace(":$name", $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * Load translations for a language
     * 
     * @param string $language Language code
     * @return void
     */
    private function loadTranslations(string $language): void
    {
        // Try to get from cache first
        $cacheKey = "translations_$language";
        $cached = $this->cacheService->get($cacheKey);
        
        if ($cached !== null) {
            $this->translations = $cached;
            return;
        }
        
        // If not in cache, load from database
        $this->translations = $this->languageModel->getUiTexts($language);
        
        // Cache for 1 hour
        $this->cacheService->set($cacheKey, $this->translations, 3600);
    }
    
    /**
     * Change the current language
     * 
     * @param string $language Language code
     * @return void
     */
    public function setLanguage(string $language): void
    {
        if ($this->currentLanguage !== $language) {
            $this->currentLanguage = $language;
            $this->loadTranslations($language);
        }
    }
    
    /**
     * Get the current language
     * 
     * @return string Current language code
     */
    public function getLanguage(): string
    {
        return $this->currentLanguage;
    }
    
    /**
     * Check if a translation exists
     * 
     * @param string $key Translation key
     * @return bool True if translation exists
     */
    public function has(string $key): bool
    {
        return isset($this->translations[$key]);
    }
    
    /**
     * Get all translations for the current language
     * 
     * @return array All translations
     */
    public function getAll(): array
    {
        return $this->translations;
    }
    
    /**
     * Clear translation cache
     * 
     * @param string|null $language Specific language to clear, null for all
     * @return bool True if successful
     */
    public function clearCache(?string $language = null): bool
    {
        if ($language) {
            return $this->cacheService->delete("translations_$language");
        }
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();
        
        foreach ($languages as $lang) {
            $this->cacheService->delete("translations_{$lang->code}");
        }
        
        return true;
    }
}
