<?php

/**
 * Session class for the MVC framework
 *
 *
 * Filename:        Session.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

namespace Framework;

class Session
{
  /**
   * Start the session
   *
   * @return void
   */
  public static function start(): void
  {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  }

  /**
   * Set a session key/value pair
   *
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public static function set(string $key, mixed $value): void
  {
    $_SESSION[$key] = $value;
  }

  /**
   * Check if session key exists
   *
   * @param string $key
   * @return boolean
   */
  public static function has(string $key): bool
  {
    return isset($_SESSION[$key]);
  }

  /**
   * Get a session value by the key
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public static function get(string $key, mixed $default = null): mixed
  {
    return $_SESSION[$key] ?? $default;
    // return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
  }

  /**
   * Clear session by key
   *
   * @param string $key
   * @return void
   */
  public static function clear(string $key): void
  {
    if (isset($_SESSION[$key])) {
      unset($_SESSION[$key]);
    }
  }

  /**
   * Clear all session data
   *
   * @return void
   */
  public static function clearAll(): void
  {
    session_unset();
    session_destroy();
  }

  /**
   * Set a flash message by the key
   *
   * @param string $key
   * @param string $message
   * @return void
   */
  public static function setFlashMessage(string $key, string $message): void
  {
    self::set('flash_' . $key, $message);
  }

  /**
   * Get a flash message by the key
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public static function getFlashMessage(string $key, mixed $default = null): mixed
  {
    $message  = self::get('flash_' . $key, $default);
    selF::clear('flash_' . $key);
    return $message;
  }
}
