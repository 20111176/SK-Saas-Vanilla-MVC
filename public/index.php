<?php

/**
 * index
 *
 * DESCRIPTION OF THE PURPOSE AND USE OF THE CODE
 *
 * Filename:        index.php
 * Location:        public/index.php
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


require __DIR__ . '/../vendor/autoload.php';

use Framework\Router;

require '../helpers.php';

$_ENV('DB_USER');
