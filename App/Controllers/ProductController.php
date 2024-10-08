<?php

/**
 * ProductController
 *
 * Filename:        ProductController.php
 * Location:        App/Controllers/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace App\Controllers;

use Framework\Authorisation;
use Framework\Database;
use Framework\Session;
use Framework\Validation;

class ProductController
{

  protected $db;

  public function __construct()
  {
    $config = require basePath('config/db.php');
    $this->db = new Database($config);
  }


  public function index()
  {
    $sql = "SELECT * FROM products ORDER BY created_at DESC";

    $products = $this->db->query($sql)->fetchAll();


    loadView('products/index', [
      'products' => $products
    ]);
  }


  /**
   * Show the create product form
   *
   * @return void
   */
  public function create()
  {
    loadView('products/create');
  }

  /**
   * Show a single product
   *
   * @param  array  $params
   * @return void
   */
  public function show($params)
  {
    $id = $params['id'] ?? '';

    $params = [
      'id' => $id
    ];

    $sql = 'SELECT * FROM products WHERE id = :id';
    $product = $this->db->query($sql, $params)->fetch();

    // Check if product exists
    if (!$product) {
      ErrorController::notFound('Product not found');
      return;
    }

    loadView('products/show', [
      'product' => $product
    ]);
  }

  /**
   * Store data in database
   *
   * @return void
   */
  public function store()
  {
    $allowedFields = ['name', 'description', 'price'];

    $newProductData = array_intersect_key($_POST, array_flip($allowedFields));

    $newProductData['user_id'] = Session::get('user')['id'];

    $newProductData = array_map('sanitize', $newProductData);

    $requiredFields = ['name', 'price'];

    $errors = [];

    foreach ($requiredFields as $field) {
      if (empty($newProductData[$field]) || !Validation::string($newProductData[$field])) {
        $errors[$field] = ucfirst($field) . ' is required';
      }
    }

    if (!empty($errors)) {
      // Reload view with errors
      loadView('products/create', [
        'errors' => $errors,
        'product' => $newProductData
      ]);
    }

    // Save the submitted data
    $fields = [];

    foreach ($newProductData as $field => $value) {
      $fields[] = $field;
    }

    $fields = implode(', ', $fields);

    $values = [];

    foreach ($newProductData as $field => $value) {
      // Convert empty strings to null
      if ($value === '') {
        $newProductData[$field] = null;
      }
      $values[] = ':' . $field;
    }

    $values = implode(', ', $values);

    $insertQuery = "INSERT INTO products ({$fields}) VALUES ({$values})";

    $this->db->query($insertQuery, $newProductData);

    Session::setFlashMessage('success_message', 'Product created successfully');

    redirect('/products');
  }

  /**
   * Delete a product
   *
   * @param  array  $params
   * @return void|null
   * @throws \Exception
   */
  public function destroy($params)
  {
    $id = $params['id'];

    $params = [
      'id' => $id
    ];

    $product = $this->db->query('SELECT * FROM products WHERE id = :id', $params)->fetch();

    // Check if product exists
    if (!$product) {
      ErrorController::notFound('Product not found');
      exit();
    }

    // Authorisation
    if (!Authorisation::isOwner($product->user_id)) {
      Session::setFlashMessage('error_message', 'You are not authoirzed to delete this product');
      return redirect('/products/' . $product->id);
    }

    $this->db->query('DELETE FROM products WHERE id = :id', $params);

    // Set flash message
    Session::setFlashMessage('success_message', 'Product deleted successfully');

    redirect('/products');
  }

  /**
   * Show the product edit form
   *
   * @param  array  $params
   * @return null
   * @throws \Exception
   */
  public function edit($params)
  {
    $id = $params['id'] ?? '';

    $params = [
      'id' => $id
    ];

    $product = $this->db->query('SELECT * FROM products WHERE id = :id', $params)->fetch();

    // Check if product exists
    if (!$product) {
      ErrorController::notFound('Product not found');
      exit();
    }

    // Authorisation
    if (!Authorisation::isOwner($product->user_id)) {
      Session::setFlashMessage(
        'error_message',
        'You are not authorized to update this product'
      );
      return redirect('/products/' . $product->id);
    }

    loadView('products/edit', [
      'product' => $product
    ]);
  }

  /**
   * Update a product
   *
   * @param  array  $params
   * @return null
   */
  public function update($params)
  {
    $id = $params['id'] ?? '';

    $params = [
      'id' => $id
    ];

    $product = $this->db->query('SELECT * FROM products WHERE id = :id', $params)->fetch();

    // Check if product exists
    if (!$product) {
      ErrorController::notFound('Product not found');
      exit();
    }

    // Authorisation
    if (!Authorisation::isOwner($product->user_id)) {
      Session::setFlashMessage(
        'error_message',
        'You are not authorised to update this product'
      );
      return redirect('/products/' . $product->id);
    }

    $allowedFields = ['name', 'description', 'price'];

    $updateValues = array_intersect_key($_POST, array_flip($allowedFields)) ?? [];

    $updateValues = array_map('sanitize', $updateValues);

    $requiredFields = ['name', 'price'];

    $errors = [];

    foreach ($requiredFields as $field) {
      if (empty($updateValues[$field]) || !Validation::string($updateValues[$field])) {
        $errors[$field] = ucfirst($field) . ' is required';
      }
    }

    if (!empty($errors)) {
      loadView('products/edit', [
        'product' => $product,
        'errors' => $errors
      ]);
      exit;
    }

    // Submit to database
    $updateFields = [];

    foreach (array_keys($updateValues) as $field) {
      $updateFields[] = "{$field} = :{$field}";
    }

    $updateFields = implode(', ', $updateFields);

    $updateQuery = "UPDATE products SET $updateFields WHERE id = :id";

    $updateValues['id'] = $id;
    $this->db->query($updateQuery, $updateValues);

    // Set flash message
    Session::setFlashMessage('success_message', 'Product updated');

    redirect('/products/' . $id);
  }


  /**
   * Search products by keywords/location
   *
   * @return void
   */
  public function search()
  {
    // I couldn't find why The super global GET cannot get the value
    // so instead of access Get value try to work alternate way to work  
    $keywords = explode("?keywords=", $_SERVER['REQUEST_URI']);
    $keywords = (end($keywords));

    // Below is original code
    // $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
    $query = "SELECT * FROM products WHERE name LIKE :keywords OR description LIKE :keywords ";

    $params = [
      'keywords' => "%{$keywords}%",
    ];

    $products = $this->db->query($query, $params)->fetchAll();

    loadView('/products/index', [
      'keywords' => $keywords,
      'products' => $products,
    ]);
  }
}
