<?php

/**
 * Handling the authorisation of users.
 *
 * Filename:        Authorisation.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

namespace Framework;

class Authorisation
{
  /**
   * Check if the user is the owner of a resource.
   *
   * @param string $resourceId
   * @return boolean
   */
  public static function isOwner(string $resourceId): bool
  {
    $sessionUser = Session::get('user');

    if ($sessionUser !== null && isset($sessionUser['id'])) {
      $sessionUserId = (int)$sessionUser['id'];
      return $sessionUserId === $resourceId;
    }
    return false;
  }
}
