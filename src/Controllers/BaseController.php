<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Exceptions\ControllerException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

/**
 * Abstract Base Controller
 * 
 * Provides common rendering functionality for all controllers
 * 
 * @package App\Core
 */
abstract class BaseController
{
    /**
     * Render a Twig template with provided data
     * 
     * This method encapsulates the Twig rendering process, 
     * providing a consistent way to render views across all controllers.
     * 
     * Key features:
     * - Dynamically locates template directory
     * - Configures Twig environment
     * - Adds global variables
     * - Supports debug mode
     * 
     * @param string $view Name of the view template (without .html.twig extension)
     * @param array $data Associative array of data to pass to the template
     * @return string Rendered HTML content
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function render(string $view, array $data = []): string
    {
        try{
            // Dynamically determine template directory 
            // Uses App/templates as the base directory
            $templateDir = dirname(__DIR__, 2) . '/templates';
            
            // Create Twig loader with template directory
            $loader = new FilesystemLoader($templateDir);
            
            // Initialize Twig environment with configuration
            $twig = new Environment($loader, [
                // Cache directory for compiled templates
                'cache' => dirname(__DIR__, 2) . '/var/cache/twig',
                
                // Use debug mode from environment configuration
                'debug' => $_ENV['APP_DEBUG'] ?? false,
                
                // Automatically reload templates when they change
                'auto_reload' => true
            ]);
            
            // Add global variables accessible in all templates
            $twig->addGlobal('app_name', 'PANIKPA');
            $twig->addGlobal('current_year', date('Y'));
            
            // Add static domain for asset loading
            $twig->addGlobal('static_domain', $_ENV['STATIC_URL'] ?? 'localhost');
            
            // Add Twig debug extension in debug mode
            if ($_ENV['APP_DEBUG'] ?? false) {
                $twig->addExtension(new DebugExtension());
            }
            
            // Merge default data with request-specific data
            $defaultData = [
                'request' => new RequestObject(), // Default empty request object
                'success' => [], // Default empty success messages
                'error' => [],    // Default empty error messages
                'current_path' => $_SERVER['REQUEST_URI'] ?? '/',
            ];
            $data = array_merge($defaultData, $data);
            
            // Render the template and return the result
            return $twig->render($view . '.html.twig', $data);
        } catch (\Exception $e) {
            throw new ControllerException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Render and output a view directly
     * 
     * Useful for controllers that want to immediately echo the rendered view
     * 
     * @param string $view Name of the view template
     * @param array $data Data to pass to the template
     * @return void
     */
    protected function renderView(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }
    
    /**
     * Redirect to a specified URL
     * 
     * @param string $url Target URL
     * @param array $params Optional query parameters
     * @param int $statusCode HTTP status code (default 302 Found)
     * @return void
     */
    protected function redirect(
        string $url, 
        array $params = [], 
        int $statusCode = 302
    ): void {
        // Build query string if params are provided
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Set HTTP status code and perform redirect
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
}