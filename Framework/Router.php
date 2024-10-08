<?php

/**
 * Router
 *
 * DESCRIPTION OF THE PURPOSE AND USE OF THE CODE
 *
 * Filename:        Router.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

namespace Framework;

use App\Controllers\ErrorController;
use Framework\Middleware\Authorise;

class Router
{
  protected $routes = [];

  public function registerRoute($method, $uri, $action, $middleware = []): void
  {
    list($controller, $controllerMethod) = explode('@', $action);

    $this->routes[] = [
      'method' => $method,
      'uri'    => $uri,
      'controller' => $controller,
      'controllerMethod' => $controllerMethod,
      'Middleware' => $middleware,
    ];
  }

  public function get($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('GET', $uri, $controller, $middleware);
  }

  public function post($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('POST', $uri, $controller, $middleware);
  }

  public function put($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('PUT', $uri, $controller, $middleware);
  }

  public function delete($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('DELETE', $uri, $controller, $middleware);
  }

  public function route($uri): void
  {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    // Check for _method input
    if ($requestMethod == 'POST' && isset($_POST['_method'])) {
      // Override the request method with the value of _method
      $requestMethod = strtoupper($_POST['_method']);
    }
    foreach ($this->routes as $route) {
      // split the current URI into segments
      $uriSegments = explode('/', trim($uri, '/'));

      // split the route URI into segments
      $routeSegments = explode('/', trim($route['uri'], '/'));

      $match = true;

      if (
        count($uriSegments) === count($routeSegments)
        && strtoupper($route['method'] === $requestMethod)
      ) {
        $params = [];

        $match = true;
        $segments = count($uriSegments);
        for ($i = 0; $i < $segments; $i++) {
          // If the uri's do not match and there is no param
          if (
            $routeSegments[$i] !== $uriSegments[$i]
            && !preg_match('/\{(.+?)}/', $routeSegments[$i])
          ) {
            $match = false;
            break;
          }
          // Check for the param and add to $params array
          // eg /products/{id} matched against /products/23454
          if (preg_match('/\{(.+?)}/', $routeSegments[$i], $matches)) {
            $params[$matches[1]] = $uriSegments[$i];
          }
        }
        if ($match) {
          foreach ($route['Middleware'] as $middleware) {
            (new Authorise())->handle($middleware);
          }

          $controller = 'App\\Controllers\\' . $route['controller'];
          $controllerMethod = $route['controllerMethod'];

          // Instantiate the controller and call the method  
          $controllerInstance = new $controller();
          $controllerInstance->$controllerMethod($params);
          return;
        }
      }
    }
    ErrorController::notFound();
  }
}
