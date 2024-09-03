<?php

/**
 * Authorise
 *
 * DESCRIPTION OF THE PURPOSE AND USE OF THE CODE
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
  public function isAuthenticated()
  {
    return Session::has('user');
  }

  public function handle($role)
  {
    if ($role == 'guest' && $this->isAuthenticated()) {
      return redirect('/');
    }

    if ($role === 'auth' && !$this->isAuthenticated()) {
      return redirect('/auth/login');
    }
  }
}
