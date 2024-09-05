<?php

/**
 * HomeController
 *
 * Filename:        HomeController.php
 * Location:        App/Controllers/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace App\Controllers;

use Framework\Database;

class HomeController
{
  protected $db;

  public function __construct()
  {
    $config = require basePath('config/db.php');
    $this->db = new Database($config);
  }

  /*
     * Show the latest products
     *
     * @return void
     */
  public function index()
  {
    $lastSixQuery = 'SELECT * FROM products ORDER BY created_at DESC LIMIT 0,6';
    $simpleRandomSixQuery = 'SELECT * FROM products ORDER BY RAND() LIMIT 0,6';

    $products = $this->db->query($simpleRandomSixQuery)
      ->fetchAll();

    $productCount = $this->db->query('SELECT count(id) as total FROM products ')
      ->fetch();

    $userCount = $this->db->query('SELECT count(id) as total FROM users')
      ->fetch();

    loadView('home', [
      'products' => $products,
      'productCount' => $productCount,
      'userCount' => $userCount,
    ]);
  }
}
