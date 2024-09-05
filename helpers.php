<?php

/**
 * helpers
 *
 * DESCRIPTION OF THE PURPOSE AND USE OF THE CODE
 *
 * Filename:        helpers.php
 * Location:        /
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

/**
 * Get the base path (operating system)
 *
 * @param string $path
 * @return string
 */
function basePath(string $path = ''): string
{
  return __DIR__ . DIRECTORY_SEPARATOR . $path;
}

/**
 * Load a view
 *
 * @param string $name
 * @param array $data
 * @return void
 */
function loadView(string $name, $data = []): void
{
  $viewPath = basePath("App/views/{$name}.view.php");

  if (file_exists($viewPath)) {
    extract($data);
    require $viewPath;
  } else {
    echo "View '{$name} not found!";
  }
}


/**
 * Load a partial
 *
 * @param string $name
 * @param array $data
 * @return void
 */
function loadPartial(string $name, $data = []): void
{
  $partialPath = basePath("App/views/partials/{$name}.view.php");

  if (file_exists($partialPath)) {
    extract($data);
    require $partialPath;
  } else {
    echo "Partial '{$name} not found!";
  }
}

/**
 * Inspect a value in the browser
 *
 * @param mixed $value
 * @return void
 */
function inspect(mixed $value): void
{
  echo '<pre>';
  var_dump($value);
  echo '</pre>';
}

/**
 * Inspect a value and stop running
 *
 * @param mixed $value
 * @return void
 */
function inspectAndDie(mixed $value): void
{
  echo '<pre>';
  var_dump($value);
  echo '</pre>';
  die();
}

/**
 * Dump the value of one or more variable, objects or similar.
 *
 * @return void
 */
function dump(): void
{
  echo "<pre class='bg-gray-100 color-black m-2 p2 rounded shadow flex-grow text-sm'>";
  array_map(function ($x) {
    var_dump($x);
  }, func_get_args());
  echo "</pre>";
}

/**
 * Dump te values of one or more variables, objects or similar, then terminate the script.
 *
 * @return void
 */
function dd(): void
{
  echo "<pre class='bg-gray-100 color-black m-2 p2 rounded shadow flex-grow text-sm'>";
  array_map(function ($x) {
    var_dump($x);
  }, func_get_args());
  echo "</pre>";
  die();
}

/**
 * Sanitize data
 *
 * @param string $dirty
 * @return string
 */
function sanitize(string $dirty): string
{
  return filter_var(trim($dirty), FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Redirect given url
 *
 * @param string $url
 * @return void
 */
function redirect(string $url): void
{
  header("Location: {$url}");
  exit();
}
