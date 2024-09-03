<?php

/**
 * Session
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


//TODO:: add descriptions each function

namespace Framework;

class Session
{
  public static function start()
  {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  }

  public static function set($key, $value)
  {
    $_SESSION[$key] = $value;
  }

  public static function has($key)
  {
    return isset($_SESSION[$key]);
  }

  public static function get($key, $default = null)
  {
    return $_SESSION[$key] ?? $default;
    // return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
  }

  public static function clear($key)
  {
    if (isset($_SESSION[$key])) {
      unset($_SESSION[$key]);
    }
  }

  public static function clearAll()
  {
    session_unset();
    session_destroy();
  }

  public static function setFlashMessage($key, $message)
  {
    self::set('flash_' . $key, $message);
  }

  public static function getFlashMessage($key, $default = null)
  {
    $message  = self::get('flash_' . $key, $default);
    selF::clear('flash_' . $key);
    return $message;
  }
}
