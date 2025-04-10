<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Services\TranslationService;

/**
 * ErrorController - Handles error pages
 * 
 * This controller is responsible for rendering error pages
 * such as a 404 Not Found and 500 Server Error.
 */
class ErrorController extends BaseController
{
    /**
     * Display 404 Not Found page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function notFound(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(404);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render 404 page
        echo $this->render('errors/404', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display 500 Server Error page
     * 
     * @param RequestObject $request Current request information
     * @param \Exception|null $exception The exception that caused the error
     * @return void
     */
    public function serverError(RequestObject $request, ?\Exception $exception = null): void
    {
        // Set HTTP status code
        http_response_code(500);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Log the exception
        if ($exception) {
            error_log($exception->getMessage());
            error_log($exception->getTraceAsString());
        }
        
        // Only show exception details in debug mode
        $exceptionDetails = null;
        if ($_ENV['APP_DEBUG'] === 'true' && $exception) {
            $exceptionDetails = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        // Render 500 page
        echo $this->render('errors/500', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'exception' => $exceptionDetails
        ]);
    }
    
    /**
     * Display maintenance mode page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function maintenance(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(503);
        
        // Set retry-after header (1 hour)
        header('Retry-After: 3600');
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render maintenance page
        echo $this->render('errors/maintenance', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display forbidden page (403)
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forbidden(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(403);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render 403 page
        echo $this->render('errors/403', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
}
