<?php

namespace App\Middleware;

use App\Core\RequestObject;
use App\Models\LanguageModel;
use App\Services\Database;

/**
 * LanguageMiddleware - Language detection and switching middleware
 * 
 * This middleware handles language detection and switching based on
 * URL path, query parameters, and session state.
 */
class LanguageMiddleware
{
    private RequestObject $request;
    private array $availableLanguages;
    private string $defaultLanguage;
    
    /**
     * Create a new LanguageMiddleware instance
     * 
     * @param RequestObject $request Current request object
     */
    public function __construct(RequestObject $request)
    {
        $this->request = $request;
        $this->defaultLanguage = $_ENV['DEFAULT_LANGUAGE'] ?? 'en';
        
        // Get available languages from database
        $database = new Database();
        $languageModel = new LanguageModel($database);
        $languages = $languageModel->getActiveLanguages();
        
        // Format into a simpler array
        $this->availableLanguages = array_map(function($lang) {
            return $lang->code;
        }, $languages);
        
        // Ensure default language is in available languages
        if (!in_array($this->defaultLanguage, $this->availableLanguages)) {
            // If default language is not available, use the first available language
            $this->defaultLanguage = $this->availableLanguages[0] ?? 'en';
        }
    }
    
    /**
     * Handle language detection and selection
     * 
     * @return RequestObject Updated request object with language
     */
    public function handle(): RequestObject
    {
        // Priority 1: Check for language query parameter
        $langParam = $_GET['lang'] ?? null;
        if ($langParam && in_array($langParam, $this->availableLanguages)) {
            // Set the language in session
            $_SESSION['language'] = $langParam;
            $this->request->setLanguageCode($langParam);
            return $this->request;
        }
        
        // Priority 2: Check language in session
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->availableLanguages)) {
            $this->request->setLanguageCode($_SESSION['language']);
            return $this->request;
        }
        
        // Priority 3: Check language in URL path
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Check for English pages
        if (preg_match('/\b(en|english)\b/i', $path)) {
            $_SESSION['language'] = 'en';
            $this->request->setLanguageCode('en');
            return $this->request;
        }
        
        // Check for French pages
        if (preg_match('/\b(fr|french|francais|français)\b/i', $path)) {
            $_SESSION['language'] = 'fr';
            $this->request->setLanguageCode('fr');
            return $this->request;
        }
        
        // Priority 4: Check Accept-Language header
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $preferredLanguages = $this->parseAcceptLanguage($acceptLanguage);
        
        foreach ($preferredLanguages as $lang => $quality) {
            $langShort = substr($lang, 0, 2); // Extract first two characters (e.g., "en" from "en-US")
            if (in_array($langShort, $this->availableLanguages)) {
                $_SESSION['language'] = $langShort;
                $this->request->setLanguageCode($langShort);
                return $this->request;
            }
        }
        
        // Default: Use default language
        $_SESSION['language'] = $this->defaultLanguage;
        $this->request->setLanguageCode($this->defaultLanguage);
        return $this->request;
    }
    
    /**
     * Parse the Accept-Language header value
     * 
     * @param string $header Accept-Language header value
     * @return array Associative array of language => quality
     */
    private function parseAcceptLanguage(string $header): array
    {
        $result = [];
        
        // Split the header by comma
        $parts = explode(',', $header);
        
        foreach ($parts as $part) {
            // Split by semicolon to separate language and quality
            $subParts = explode(';', trim($part));
            
            $lang = $subParts[0];
            
            // Default quality is 1.0
            $quality = 1.0;
            
            // Parse quality if provided
            if (isset($subParts[1])) {
                $qValue = str_replace('q=', '', $subParts[1]);
                $quality = (float) $qValue;
            }
            
            $result[$lang] = $quality;
        }
        
        // Sort by quality (highest first)
        arsort($result);
        
        return $result;
    }
}
