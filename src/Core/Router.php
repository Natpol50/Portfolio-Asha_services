<?php

namespace App\Core;

use App\Exceptions\NotFoundException;

/**
 * Router - Handles routing of HTTP requests to controller actions
 * 
 * This class manages the application's routes and dispatches
 * requests to the appropriate controller actions.
 */
class Router
{
    private array $routes = [];
    
    /**
     * Register a GET route
     * 
     * @param string $path The route path
     * @param string $action The controller@action string
     */
    public function get(string $path, string $action): void
    {
        $this->addRoute('GET', $path, $action);
    }
    
    /**
     * Register a POST route
     * 
     * @param string $path The route path
     * @param string $action The controller@action string
     */
    public function post(string $path, string $action): void
    {
        $this->addRoute('POST', $path, $action);
    }
    
    /**
     * Register a PUT route
     * 
     * @param string $path The route path
     * @param string $action The controller@action string
     */
    public function put(string $path, string $action): void
    {
        $this->addRoute('PUT', $path, $action);
    }
    
    /**
     * Register a DELETE route
     * 
     * @param string $path The route path
     * @param string $action The controller@action string
     */
    public function delete(string $path, string $action): void
    {
        $this->addRoute('DELETE', $path, $action);
    }
    
    /**
     * Add a route to the routes array
     * 
     * @param string $method The HTTP method
     * @param string $path The route path
     * @param string $action The controller@action string
     */
    private function addRoute(string $method, string $path, string $action): void
    {
        // Convert path parameters to regex pattern
        $pattern = $this->pathToPattern($path);
        
        $this->routes[$method][$pattern] = [
            'path' => $path,
            'action' => $action
        ];
    }
    
    /**
     * Convert a path with parameters to a regex pattern
     * 
     * @param string $path The path with parameters
     * @return string The regex pattern
     */
    private function pathToPattern(string $path): string
    {
        // Replace {parameter} with regex pattern
        $pattern = preg_replace('#{([a-zA-Z0-9_]+)}#', '(?<$1>[^/]+)', $path);
        
        // Escape forward slashes and add start/end anchors
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatch a request to the appropriate controller action
     * 
     * @param string $method The HTTP method
     * @param string $uri The request URI
     * @param RequestObject $request The request object
     * @throws NotFoundException If no matching route is found
     */
    public function dispatch(string $method, string $uri, RequestObject $request): void
    {
        // Check if method routes exist
        if (!isset($this->routes[$method])) {
            throw new NotFoundException("No routes defined for $method method");
        }
        
        // Look for a matching route
        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Extract path parameters
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);
                
                // Get controller and action
                list($controllerName, $actionName) = explode('@', $route['action']);
                
                // Create the full controller class name
                $controllerClass = "App\\Controllers\\$controllerName";
                
                // Create controller instance
                $controller = new $controllerClass();
                
                // Call the action with parameters
                $controller->$actionName($request, ...$params);
                
                return;
            }
        }
        
        // No matching route found
        throw new NotFoundException("No route found for $method $uri");
    }
}
