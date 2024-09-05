<?php

/**
 * Authorise middleware with session data
 *
 * Filename:        Authorise.php
 * Location:        Framework/Middleware/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

namespace Framework\Middleware;

use Framework\Session;

class Authorise
{
  /**
   * Check if user is authenticated.
   *
   * @return boolean
   */
  public function isAuthenticated(): bool
  {
    return Session::has('user');
  }

  /**
   * Handling the middleware check is it guest or authenticated.
   *
   * @param string $role
   * @return void
   */
  public function handle(string $role)
  {
    if ($role == 'guest' && $this->isAuthenticated()) {
      return redirect('/'); // return void?
      // redirect('/');
    }

    if ($role === 'auth' && !$this->isAuthenticated()) {
      return redirect('/auth/login'); // return void?
      // redirect('/auth/login');
    }
  }
}
