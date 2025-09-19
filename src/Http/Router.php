<?php

namespace App\Http;

class Router
{
  private array $routes = [];

  public function add(string $method, string $path, callable $handler): void
  {
    $method = strtoupper($method);
    $this->routes[$method . ' ' . $path] = $handler;
  }

  public function dispatch(string $method, string $uri)
  {
    $method = strtoupper($method);
    // Determine request path; include PATH_INFO when front-controller style is used
    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (is_string($pathInfo) && $pathInfo !== '') {
      // Normalize double slashes
      $path = rtrim($path, '/') . '/' . ltrim($pathInfo, '/');
    }
    // Support route passed via query string: index.php?route=/login
    if (!empty($_GET['route']) && is_string($_GET['route'])) {
      $qsRoute = (string)$_GET['route'];
      // If route contains a query string, strip it (params are already in main query)
      $qsRoute = parse_url($qsRoute, PHP_URL_PATH) ?? $qsRoute;
      $path = rtrim($path, '/') . '/' . ltrim($qsRoute, '/');
    }
    $key = $method . ' ' . rtrim($path, '/') ?: '/';
    if (isset($this->routes[$key])) {
      return ($this->routes[$key])();
    }
    // Support path params: e.g., /agents/{id}
    foreach ($this->routes as $routeKey => $handler) {
      [$m, $pattern] = explode(' ', $routeKey, 2);
      if ($m !== $method) continue;
      $regex = preg_replace('#\{[^/]+\}#', '([^/]+)', str_replace('/', '\/', $pattern));
      $regex = '#^' . $regex . '$#';
      if (preg_match($regex, $path, $matches)) {
        array_shift($matches);
        return $handler(...$matches);
      }
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    return null;
  }
}


