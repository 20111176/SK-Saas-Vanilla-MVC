<?php

/**
 * ErrorController
 *
 * Filename:        ErrorController.php
 * Location:        App/Controllers/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace App\Controllers;

class ErrorController
{
  /*
       * 404 not found error
       *
       * @return void
       */
  public static function notFound($message = 'Resource not found')
  {
    http_response_code(404);

    loadView('error', [
      'status' => '404',
      'message' => $message
    ]);
  }

  /*
     * 403 unauthorized error
     *
     * @return void
     */
  public static function unauthorized($message = 'You are not authorized to view this resource')
  {
    http_response_code(403);

    loadView('error', [
      'status' => '403',
      'message' => $message
    ]);
  }
}
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
    $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
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
<?php

/**
 * UserController
 *
 * Filename:        UserController.php
 * Location:        App/Controllers/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace App\Controllers;

use Framework\Database;
use Framework\Session;
use Framework\Validation;

class UserController
{

  /* Properties */

  /**
   * @var Database
   */
  protected $db;

  /**
   * UserController Constructor
   *
   * Instantiate the database connection for use in this class
   * storing the connection in the protected <code>$db</code>
   * property.
   *
   * @throws \Exception
   */
  public function __construct()
  {
    $config = require basePath('config/db.php');
    $this->db = new Database($config);
  }

  /**
   * Show the login page
   *
   * @return void
   */
  public function login()
  {
    loadView('users/login');
  }

  /**
   * Show the register page
   *
   * @return void
   */
  public function create()
  {
    loadView('users/create');
  }

  /**
   * Store user in database
   *
   * @return void
   */
  public function store()
  {
    $name = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $password = $_POST['password'] ?? null;
    $passwordConfirmation = $_POST['password_confirmation'] ?? null;

    $errors = [];

    // Validation
    if (!Validation::email($email)) {
      $errors['email'] = 'Please enter a valid email address';
    }

    if (!Validation::string($name, 2, 50)) {
      $errors['name'] = 'Name must be between 2 and 50 characters';
    }

    if (!Validation::string($password, 6, 50)) {
      $errors['password'] = 'Password must be at least 6 characters';
    }

    if (!Validation::match($password, $passwordConfirmation)) {
      $errors['password_confirmation'] = 'Passwords do not match';
    }

    if (!empty($errors)) {
      loadView('users/create', [
        'errors' => $errors,
        'user' => [
          'name' => $name,
          'email' => $email,
          'city' => $city,
          'state' => $state,
        ]
      ]);
      exit;
    }

    // Check if email exists
    $params = [
      'email' => $email
    ];

    $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();

    if ($user) {
      $errors['email'] = 'That email already exists';
      loadView('users/create', [
        'errors' => $errors
      ]);
      exit;
    }

    // Create user account 
    $params = [
      'name' => $name,
      'email' => $email,
      'city' => $city,
      'state' => $state,
      'password' => password_hash($password, PASSWORD_DEFAULT)
    ];

    $this->db->query('INSERT INTO users (name, email, city, state, password) VALUES (:name, :email, :city, :state, :password)', $params);

    // Get new user ID
    $userId = $this->db->conn->lastInsertId();

    // Set user session
    Session::set('user', [
      'id' => $userId,
      'name' => $name,
      'email' => $email,
      'city' => $city,
      'state' => $state
    ]);

    redirect('/');
  }

  /**
   * Logout a user and kill session
   *
   * @return void
   */
  public function logout()
  {
    Session::clearAll();

    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain']);

    redirect('/');
  }

  /**
   * Authenticate a user with email and password
   *
   * @return void
   */
  public function authenticate()
  {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $errors = [];

    // Validation
    if (!Validation::email($email)) {
      $errors['email'] = 'Please enter a valid email';
    }

    if (!Validation::string($password, 6, 50)) {
      $errors['password'] = 'Password must be at least 6 characters';
    }

    // Check for errors
    if (!empty($errors)) {
      loadView('users/login', [
        'errors' => $errors
      ]);
      exit;
    }

    // Check for email
    $params = [
      'email' => $email
    ];

    $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();

    if (!$user) {
      $errors['email'] = 'Incorrect credentials';
      loadView('users/login', [
        'errors' => $errors
      ]);
      exit;
    }

    // Check if password is correct
    if (!password_verify($password, $user->password)) {
      $errors['email'] = 'Incorrect credentials';
      loadView('users/login', [
        'errors' => $errors
      ]);
      exit;
    }

    // Set user session
    Session::set('user', [
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'city' => $user->city,
      'state' => $user->state
    ]);

    redirect('/');
  }
}
<?php

/**
 * 403.view
 *
 * Filename:        403.view.php
 * Location:        App/views/errors/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


require_once basePath("App/views/partials/header.view.php");

loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg">
  <article>
    <header class="bg-text-zinc-700-700 text-text-zinc-700-200 -mx-4 -mt-8 p-8 text-2xl font-bold mb-8">
      <h1>Bad User</h1>
    </header>

    <section class="text-lg">
      <p>You are not allowed to visit this page...</p>
    </section>

  </article>
</main>


<?php
require_once basePath("App/views/partials/footer.view.php");
?>
<?php

/**
 * 404.view
 *
 * Filename:        404.view.php
 * Location:        App/views/errors/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

require_once basePath("App/views/partials/header.view.php");

loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg">
  <article>
    <header class="bg-text-zinc-700-700 text-text-zinc-700-200 -mx-4 -mt-8 p-8 text-2xl font-bold mb-8">
      <h1>WHOOPSIE!</h1>
    </header>
    <section class="text-lg">
      <p>Sorry to say that the cat did a whoopsie and could not find the file you were looking for...</p>
    </section>

  </article>
</main>


<?php
require_once basePath("App/views/partials/footer.view.php");
?>
<?php

/**
 * Error view file for the error controller
 *
 * Filename:        error.view.php
 * Location:        App/views/partials/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

if (isset($errors) && count($errors) > 0): ?>
  <div class="flex w-full shadow-lg rounded-lg my-4">
    <div class="bg-red-600 py-2 px-6 rounded-l-lg flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" class="fill-current text-white" width="20"
        height="20">
        <path fill-rule="evenodd"
          d="M4.47.22A.75.75 0 015 0h6a.75.75 0 01.53.22l4.25 4.25c.141.14.22.331.22.53v6a.75.75 0
            01-.22.53l-4.25 4.25A.75.75 0 0111 16H5a.75.75 0 01-.53-.22L.22 11.53A.75.75 0 010
            11V5a.75.75 0 01.22-.53L4.47.22zm.84 1.28L1.5 5.31v5.38l3.81 3.81h5.38l3.81-3.81V5.31L10.69
            1.5H5.31zM8 4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 018 4zm0 8a1 1 0
            100-2 1 1 0 000 2z"></path>
      </svg>
    </div>
    <div class="px-4 py-2 bg-white rounded-r-lg flex flex-col justify-between items-left w-full
                border border-l-transparent border-gray-200">
      <?php
      foreach ($errors as $error) : ?>

        <p>
          <?= $error ?>
        </p>
      <?php
      endforeach;
      ?>
    </div>
  </div>
<?php
endif;
<?php

/**
 * footer.view
 *
 * Filename:        footer.view.php
 * Location:        App/views/partials/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */
?>
<footer class="bg-black text-zinc-500 p-4 mt-8 flex flex-wrap flex-row text-sm">
  <section class="w-1/2 p-8 flex flex-col gap-2">
    <p class="text-zinc-400">&copy; Copyright 2024 Sukhwan Ko. All rights reserved.</p>
    <p class="text-xs">
      Based on the <a href="https://github.com/20111176/SK-Saas-Vanilla-MVC"
        class="text-zinc-500 hover:text-white underline underline-offset-2">tutorial</a>
      by <a href="https://yacmov.github.io"
        class="text-zinc-500 hover:text-white">Sukhwan Ko</a> &
      <a href="https://adygcode.github.io"
        class="text-zinc-500 hover:text-red-700">North Metropolitan TAFE</a>
    </p>
    <p>License: MIT Open Source Licensing</p>
  </section>
  <section class="w-1/2 p-8 text-xs">
    <ul class=" flex flex-col gap-1">
      <li><a href="#" class="text-zinc-500 hover:text-white underline underline-offset-2">Link 1</a></li>
      <li><a href="#" class="text-zinc-500 hover:text-white underline underline-offset-2">Link 2</a></li>
      <li><a href="#" class="text-zinc-500 hover:text-white underline underline-offset-2">Link 3</a></li>
      <li><a href="#" class="text-zinc-500 hover:text-white underline underline-offset-2">Link 4</a></li>
    </ul>
  </section>

</footer>

</body>

</html>
<?php

/**
 * Header template - {HTML 'head'}
 *
 * Filename:        header.view.php
 * Location:        App/views/partials/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="/assets/css/site.css">
</head>

<body class="bg-white flex flex-col h-screen justify-between">
<?php

/**
 * Flash Message Partial View
 *
 * Filename:        message.view.php
 * Location:        App/views/partials/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

use Framework\Session;

$successMessage = Session::getFlashMessage('success_message');
if ($successMessage !== null) : ?>
  <div class="flex w-full shadow-lg rounded-lg my-4">
    <div class="bg-green-600 py-2 px-6 rounded-l-lg flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="text-white fill-current" viewBox="0 0 16 16" width="20"
        height="20">
        <path fill-rule="evenodd"
          d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6
          10.94l6.72-6.72a.75.75 0 011.06 0z"></path>
      </svg>
    </div>
    <div class="px-4 py-2 bg-white rounded-r-lg flex justify-between items-center w-full border
                border-l-transparent border-gray-200">
      <?= $successMessage ?>
    </div>
  </div>
<?php
endif;

$errorMessage = Session::getFlashMessage('error_message');
if ($errorMessage !== null) : ?>
  <div class="flex w-full shadow-lg rounded-lg my-4">
    <div class="bg-red-600 py-2 px-6 rounded-l-lg flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" class="fill-current text-white" width="20"
        height="20">
        <path fill-rule="evenodd"
          d="M4.47.22A.75.75 0 015 0h6a.75.75 0 01.53.22l4.25 4.25c.141.14.22.331.22.53v6a.75.75 0
            01-.22.53l-4.25 4.25A.75.75 0 0111 16H5a.75.75 0 01-.53-.22L.22 11.53A.75.75 0 010 11V5a.75.75
            0 01.22-.53L4.47.22zm.84 1.28L1.5 5.31v5.38l3.81 3.81h5.38l3.81-3.81V5.31L10.69 1.5H5.31zM8
            4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 018 4zm0 8a1 1 0 100-2 1 1 0 000 2z">
        </path>
      </svg>
    </div>
    <div class="px-4 py-2 bg-white rounded-r-lg flex justify-between items-center w-full border
                border-l-transparent border-gray-200">
      <?= $errorMessage ?>
    </div>
  </div>
<?php
endif;
<?php

/**
 * Page 'Header' and Navigation
 *
 * Filename:        navigation.view.php
 * Location:        App/views/partials/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

use Framework\Middleware\Authorise;

$authenticated = new Authorise();
?>

<header class="bg-black text-white p-4 flex-grow-0 flex flex-row align-middle content-center">
  <h1 class="flex-0 w-32 text-xl p-4 ">
    <a href="#"
      class="py-4 px-4 -mx-4 -my-4 font-bold rounded text-sky-300 hover:text-sky-700 hover:bg-sky-300
            transition ease-in-out duration-500">
      MVC
    </a>
  </h1>
  <nav class="flex flex-row gap-4 py-4 flex-grow">

    <p><a href="/"
        class="pb-2 px-1 text-text-zinc-700-200 hover:text-sky-300
              border-0 border-b-2 hover:border-b-sky-500
              transition ease-in-out duration-500">
        Home
      </a></p>

    <p><a href="/products"
        class="pb-2 px-1 text-text-zinc-700-200 hover:text-sky-300
              border-0 border-b-2 hover:border-b-sky-500
              transition ease-in-out duration-500">
        Products
      </a></p>

    <div class="flex-grow"></div>

    <?php if ($authenticated->isAuthenticated()): ?>
      <form method="POST" action="/auth/logout" class="">
        <button class="pb-2 px-1 text-text-zinc-700-200 hover:text-sky-300
                      border-0 border-b-2 hover:border-b-sky-500
                      transition ease-in-out duration-500">
          <i class="fa fa-search"></i> Logout
        </button>
      </form>
    <?php else: ?>
      <p><a href="/auth/login"
          class="pb-2 px-1 text-text-zinc-700-200 hover:text-sky-300
                border-0 border-b-2 hover:border-b-sky-500
                transition ease-in-out duration-500">
          Login
        </a></p>
      <p><a href="/auth/register"
          class="pb-2 px-1 text-text-zinc-700-200 hover:text-sky-300
                border-0 border-b-2 hover:border-b-sky-500
                transition ease-in-out duration-500">
          Register
        </a></p>
    <?php endif; ?>

    <form method="GET" action="/products/search" class="block mx-5">
      <input type="text" name="keywords" placeholder="Product search..."
        class="w-full md:w-auto px-4 py-2 focus:outline-none" />
      <button class="w-full md:w-auto
                    bg-sky-500 hover:bg-sky-600
                    text-white
                    px-4 py-2
                    focus:outline-none transition ease-in-out duration-500">
        <i class="fa fa-search"></i> Search
      </button>
    </form>
  </nav>
</header>
<?php

/**
 * create view for products
 *
 * Filename:        create.view.php
 * Location:        App/views/products/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


loadPartial("header");
loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg flex flex-col flex-grow">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 mb-8 flex">
      <h1 class="grow text-2xl font-bold ">Products - Add</h1>
      <p class="text-md flex-0 px-8 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition ease-in-out duration-500">
        <a href="/products/create">Add Product</a>
      </p>
    </header>

    <section>

      <?= loadPartial('errors', [
        'errors' => $errors ?? []
      ]) ?>

      <form method="POST" action="/products">

        <h2 class="text-2xl font-bold mb-6 text-left text-gray-500">
          Product Information
        </h2>

        <div class="mb-4">
          <input type="text" name="name" placeholder="Product Name"
            class="w-full px-4 py-2 border rounded focus:outline-none"
            value="<?= $product['name'] ?? '' ?>" />
        </div>

        <div class="mb-4">
          <textarea name="description" placeholder="Product Description"
            class="w-full px-4 py-2 border rounded focus:outline-none"><?= $product['description'] ?? '' ?></textarea>
        </div>

        <div class="mb-4">
          <input type="text" name="price" placeholder="Price"
            class="w-full px-4 py-2 border rounded focus:outline-none"
            value="<?= $product['price'] ?? '' ?>" />
        </div>

        <button type="submit"
          class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 my-3
                rounded focus:outline-none">
          Save
        </button>

        <a href="/products"
          class="block text-center w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded
                focus:outline-none">
          Cancel
        </a>

      </form>

    </section>

  </article>
</main>


<?php
loadPartial("footer");
<?php

/**
 * edit view file for products
 *
 * Filename:        edit.view.php
 * Location:        App/views/products/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


loadPartial("header");
loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg flex flex-col flex-grow">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 mb-8 flex">
      <h1 class="grow text-2xl font-bold ">Products - Edit</h1>
      <p class="text-md flex-0 px-8 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition ease-in-out duration-500">
        <a href="/products/create">Add Product</a>
      </p>
    </header>

    <section>

      <?= loadPartial('errors', [
        'errors' => $errors ?? []
      ]) ?>

      <form method="POST" action="/products/<?= $product->id ?>">
        <input type="hidden" name="_method" value="PUT">

        <h2 class="text-2xl font-bold mb-6 text-left text-gray-500">
          Product Information
        </h2>

        <div class="mb-4">
          <input type="text" name="name" placeholder="Product Name"
            class="w-full px-4 py-2 border rounded focus:outline-none"
            value="<?= $product->name ?? '' ?>" />
        </div>

        <div class="mb-4">
          <textarea name="description" placeholder="Product Description"
            class="w-full px-4 py-2 border rounded focus:outline-none"><?= $product->description ?? '' ?></textarea>
        </div>

        <div class="mb-4">
          <input type="text" name="price" placeholder="Price"
            class="w-full px-4 py-2 border rounded focus:outline-none"
            value="<?= $product->price ?? '' ?>" />
        </div>

        <button type="submit"
          class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 my-3
                rounded focus:outline-none">
          Save
        </button>

        <a href="/products/<?= $product->id ?>"
          class="block text-center w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded
                focus:outline-none">
          Cancel
        </a>

      </form>

    </section>

  </article>
</main>


<?php
loadPartial("footer");
<?php

/**
 * index view for products
 *
 * Filename:        index.view.php
 * Location:        App/views/products/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


/* Load HTML header and navigation areas */
loadPartial("header");
loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg flex flex-col flex-grow">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 mb-8 flex">
      <h1 class="grow text-2xl font-bold ">Products</h1>
      <p class="text-md flex-0 px-8 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition ease-in-out duration-500">
        <a href="/products/create">Add Product</a>
      </p>
    </header>

    <section class="text-xl text-zinc-500 my-8">
      <?php if (isset($keywords)) : ?>
        <p>Search Results for: <?= htmlspecialchars($keywords) ?></p>
        <p><?= count($products ?? []) ?> product(s) found</p>
      <?php else : ?>
        <p>All Products</p>
      <?php endif; ?>

      <?= loadPartial('message') ?>
    </section>

    <section class="grid grid-cols-3 gap-8 ">
      <?php
      foreach ($products ?? [] as $product):
      ?>
        <!--            article>(header>h4{Name})+(section>p{Description})+(footer>p{Price})-->
        <article class="max-w-96 min-w-64 bg-white shadow rounded flex flex-col">
          <header class="bg-zinc-700 text-zinc-200 text-lg p-4 -mt-2 mb-4 rounded-t flex-0">
            <h4>
              <?= $product->name ?>
            </h4>
          </header>
          <section class="flex-grow grid grid-cols-5 px-4 py-0 gap-4">
            <p class="col-span-2 p-0 pt-2">
              <img class="w-24 h-24 " src="https://dummyimage.com/200x200/a1a1aa/fff&text=Image+Here"
                alt="">
            </p>
            <p class="col-span-3 text-zinc-600 p-0"><?= $product->description ?></p>
          </section>
          <a href="/products/<?= $product->id ?>"
            class="w-full text-center text-sm text-zinc-900 font-medium
                  bg-zinc-200 hover:bg-zinc-300 block
                  py-2 mt-4 -mb-2 rounded-b
                  transition ease-in-out duration-500">
            <div class="flex flex-start ml-4">Price: <?= $product->price / 100 ?></div>
            <div class="block item-center font-bold ">
              Details...
            </div>
          </a>
        </article>
      <?php
      endforeach
      ?>
    </section>

  </article>
</main>


<?php
loadPartial("footer");
<?php

/**
 * show view for the products
 *
 * Filename:        show.view.php
 * Location:        App/views/products/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

use Framework\Authorisation;

loadPartial("header");
loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg flex flex-col flex-grow">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 mb-8 flex">
      <h1 class="grow text-2xl font-bold ">Products - Detail</h1>
      <p class="text-md flex-0 px-8 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition ease-in-out duration-500">
        <a href="/products/create">Add Product</a>
      </p>

    </header>
    <section class="w-full bg-white shadow rounded p-4 flex flex-col gap-4">
      <h4 class="-mx-4 bg-zinc-700 text-zinc-200 text-2xl p-4 -mt-4 mb-4 rounded-t flex-0 flex justify-between">
        <?= $product->name ?>
      </h4>

      <section class="flex-grow flex flex-row">
        <h5 class="text-lg font-bold w-1/6 min-w-1/6">
          Image:
        </h5>
        <p class="grow">
          <img class="w-64 h-64 rounded-lg"
            src="https://dummyimage.com/200x200/a1a1aa/fff&text=Image+Here"
            alt="">
        </p>
      </section>

      <section class="flex-grow flex flex-row">
        <h5 class="text-lg font-bold w-1/6 min-w-1/6">
          Description:
        </h5>
        <p class="grow max-w-96 text-zinc-600 text-lg">
          <?= $product->description ?>
        </p>
      </section>

      <section class="flex-grow flex flex-row">
        <h5 class="text-lg font-bold w-1/6 min-w-1/6">
          Price:
        </h5>
        <p class="grow text-lg text-zinc-600">
          $<?= $product->price / 100 ?>
        </p>
      </section>

      <?php
      if (Authorisation::isOwner($product->user_id)) :
      ?>
        <form method="POST"
          class="px-4 py-4 mt-4 -mx-4 border-0 border-t-1 border-zinc-300 text-lg flex flex-row">
          <span class="w-1/6 min-w-1/6"></span>
          <a href="/products/edit/<?= $product->id ?>"
            class="px-16 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded transition ease-in-out duration-500">
            Edit
          </a>

          <input type="hidden" name="_method" value="DELETE">
          <button type="submit"
            class="ml-8 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded transition ease-in-out duration-500">
            Delete
          </button>
        </form>

      <?php
      endif;
      ?>

    </section>

  </article>
</main>


<?php
require_once basePath("App/views/partials/footer.view.php");
?>
<?php

/**
 * Register User View
 *
 * Filename:        create.view.php
 * Location:        App/views/users/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


loadPartial('header');
loadPartial('navigation'); ?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg
            flex justify-center items-center mt-8 w-1/2 ">

  <section class="bg-white p-8 rounded-lg shadow-md md:w-500 mx-6 w-full">

    <h2 class="text-4xl text-left font-bold mb-4">
      Register
    </h2>

    <?= loadPartial('errors', [
      'errors' => $errors ?? []
    ]) ?>

    <form method="POST" action="/auth/register">

      <section class="mb-4">
        <label for="Name" class="mt-4 pb-1">Name:</label>
        <input type="text" id="Name"
          name="name" placeholder="Full Name"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none"
          value="<?= $user['name'] ?? '' ?>" />
      </section>

      <section class="mb-4">
        <label for="Email" class="mt-4 pb-1">Email:</label>
        <input type="email" id="Email"
          name="email" placeholder="Email Address"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none"
          value="<?= $user['email'] ?? '' ?>" />
      </section>

      <section class="mb-4">
        <label for="City" class="mt-4 pb-1">City:</label>
        <input type="text" id="City"
          name="city" placeholder="City"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none"
          value="<?= $user['city'] ?? '' ?>" />
      </section>

      <section class="mb-4">
        <label for="State" class="mt-4 pb-1">State:</label>
        <input type="text" id="State"
          name="state" placeholder="State"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none"
          value="<?= $user['state'] ?? '' ?>" />
      </section>

      <section class="mb-4">
        <label for="Password" class="mt-4 pb-1">Password:</label>
        <input type="password" id="Password"
          name="password" placeholder="Password"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none" />
      </section>

      <section class="mb-4">
        <label for="PasswordConfirmation" class="mt-4 pb-1">Confirm password:</label>
        <input type="password" id="PasswordConfirmation"
          name="password_confirmation" placeholder="Confirm Password"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none" />
      </section>

      <section class="mb-4">
        <button type="submit"
          class="w-full bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded focus:outline-none
                transition ease-in-out duration-500">
          Register
        </button>
      </section>

      <section class="mb-4">
        <p class="mt-8 text-zinc-700">
          Already have an account?
          <a class="bg-sky-900 hover:bg-sky-600 text-white px-1 pb-1 rounded
                              transition ease-in-out duration-500" href="/auth/login">Login</a>
        </p>
      </section>

    </form>
  </section>
</main>

<?php
loadPartial('footer');
<?php

/**
 * login view file 
 *
 * Filename:        login.view.php
 * Location:        App/views/users/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


loadPartial('header');
loadPartial('navigation'); ?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg
            flex justify-center items-center mt-8 w-1/2 ">

  <section class="bg-white p-8 rounded-lg shadow-md md:w-500 mx-6 w-full">

    <h2 class="text-4xl text-left font-bold mb-4">
      Login
    </h2>

    <?= loadPartial('errors', [
      'errors' => $errors ?? []
    ]) ?>

    <form method="POST" action="/auth/login">

      <section class="mb-4">
        <label for="Email" class="mt-4 pb-1">Email:</label>
        <input type="email" id="Email"
          name="email" placeholder="Email Address"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none"
          value="<?= $user['email'] ?? '' ?>" />
      </section>

      <section class="mb-4">
        <label for="Password" class="mt-4 pb-1">Password:</label>
        <input type="password" id="Password"
          name="password" placeholder="Password"
          class="w-full px-4 py-2 border border-b-zinc-300 rounded focus:outline-none" />
      </section>

      <section class="mb-4">

        <button type="submit"
          class="w-full bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded
                                   focus:outline-none transition ease-in-out duration-500">
          Login
        </button>
      </section>

      <section class="mb-4">
        <p class="mt-8 text-zinc-700">
          So you are not a member...
          <a class="bg-sky-900 hover:bg-sky-600 text-white px-1 pb-1 rounded
                              transition ease-in-out duration-500"
            href="/auth/register">Register</a> now!
        </p>
      </section>

    </form>

  </section>
</main>

<?php
loadPartial('footer');
<?php

/**
 * Error message view
 *
 * Filename:        error.view.php
 * Location:        App/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

loadPartial('header');
loadPartial('navigation');

?>
<section class="container mx-auto p-4 mt-4">
  <div class="px-8 py-6 bg-red-600 text-white flex justify-between rounded">
    <div class="flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32 mr-6" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742
                          2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012
                          0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd" />
      </svg>
      <div class="flex flex-col items-left gap-4">
        <p class="text-4xl font-bold ">
          <?= $status ?>
        </p>
        <p class="text-2xl font-semibold ">
          <?= $message ?>
        </p>
        <p>
          <a class="underline underline-offset-2
                                  hover:text-black transition ease-in-out duration-500"
            href="/products">Go Back To Products</a>
        </p>
      </div>
    </div>

  </div>
</section>
<?php

loadPartial('footer');
<?php

/**
 * Home page view
 *
 * Filename:        home.view.php
 * Location:        App/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    04/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

loadPartial('header');
loadPartial('navigation');
?>
<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 text-2xl font-bold mb-8">
      <h1>Vanilla PHP MVC Demo</h1>
    </header>
    <section class="flex flex-row flex-wrap justify-center my-8 gap-8">

      <section class="w-1/4 bg-zinc-700 text-sky-300 shadow rounded p-2 flex flex-row">
        <h4 class="flex-0 w-1/2 -ml-2 mr-6 bg-sky-800 text-white text-lg p-4 -my-2 rounded-l">
          Products:
        </h4>
        <p class="grow text-4xl ml-6">
          <?= $productCount->total ?>
        </p>
      </section>

      <section class="w-1/4 bg-zinc-700 text-red-300 shadow rounded p-2 flex flex-row">
        <h4 class="flex-0 w-1/2 -ml-2 mr-6 bg-red-800 text-white text-lg p-4 -my-2 rounded-l">
          Users:
        </h4>
        <p class="grow text-4xl ml-6">
          <?= $userCount->total ?>
        </p>
      </section>

    </section>

    <section class="my-8 flex flex-wrap gap-8 justify-center">

      <?php
      foreach ($products as $product):
      ?>
        <!--            article>(header>h4{Name})+(section>p{Description})+(footer>p{Price})-->
        <article class="max-w-96 min-w-64 bg-white shadow rounded p-2 flex flex-col">
          <header class="-mx-2 bg-zinc-700 text-zinc-200 text-lg p-4 -mt-2 mb-4 rounded-t flex-0">
            <h4>
              <?= $product->name ?>
            </h4>
          </header>
          <section class="flex-grow grid grid-cols-5">
            <p class="ml-4 col-span-2">
              <img class="w-24 h-24 " src="https://dummyimage.com/200x200/a1a1aa/fff&text=Image+Here"
                alt="">
            </p>
            <p class="col-span-3 text-zinc-600"><?= $product->description ?></p>
          </section>
          <footer class="-mx-2 bg-zinc-200 text-zinc-900 text-sm px-4 py-1 mt-4 -mb-2 rounded-b flex-0">
            <p>Price: $<?= $product->price / 100 ?></p>
            <a href="/products/<?= $product->id ?>"
              class="block w-full text-center px-5 py-2.5 shadow-sm rounded border
                                  text-base font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200">
              Details
            </a>
          </footer>
        </article>

      <?php
      endforeach
      ?>
    </section>

    <section class="mx-auto w-1/2 m-8 bg-zinc-200 text-zinc-800 p-8 rounded-lg shadow">
      <dl class="flex flex-col gap-2">
        <dt>Tutorial:</dt>
        <dd>Part 1:
          <a href="https://github.com/AdyGCode/SaaS-FED-Notes/tree/main/session-07">
            https://github.com/AdyGCode/SaaS-FED-Notes/tree/main/session-07
          </a>
        </dd>
        <dd>Part 2:
          <a href="https://github.com/AdyGCode/SaaS-FED-Notes/tree/main/session-07">
            https://github.com/AdyGCode/SaaS-FED-Notes/tree/main/session-07
          </a>
        </dd>
        <dt>Source Code:</dt>
        <dd>
          <a href="https://github.com/AdyGCode/SaaS-Vanilla-MVC-Demo">
            https://github.com/AdyGCode/SaaS-Vanilla-MVC-Demo
          </a>
        </dd>
        <dt>HelpDesk</dt>
        <dd><a href="https://help.screencraft.net.au"></a></dd>
        <dt>HelpDesk FAQs</dt>
        <dd><a href="https://help.screencraft.net.au"></a>
        </dd>
        <dt>Make a HelpDesk Request (TAFE Students only)</dt>
        <dd><a href="https://help.screencraft.net.au"></a></dd>
      </dl>

    </section>

  </article>
</main>


<?php
loadPartial('footer');
?>
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
  public static function isOwner(int $resourceId): bool
  {
    $sessionUser = Session::get('user');
    if ($sessionUser !== null && isset($sessionUser['id'])) {
      $sessionUserId = (int)$sessionUser['id'];
      return $sessionUserId === $resourceId;
    }
    return false;
  }
}
<?php

/**
 * Database Access Class
 * 
 * Provides the database access tools used by our micro-framework
 *
 * Filename:        Database.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace Framework;

use Exception;
use PDO;
use PDOstatement;
use PDOException;

class Database
{
  public $conn;

  /**
   * Constructor for Database class
   *
   * @param array $config
   * 
   * @throws Exception
   */
  public function __construct(array $config)
  {
    $host = $config['host'];
    $port = $config['port'];
    $dbName = $config['dbname'];

    // dsn == data source name
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName}";

    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ];
    $userName = $config['username'];
    $userPass = $config['password'];
    try {
      $this->conn = new PDO($dsn, $userName, $userPass, $options);
    } catch (PDOException $e) {
      throw new Exception("Query failed to execute: {$e->getMessage()}");
    }
  }


  /**
   * The SQL to execute and on optional array of named parameters and values are required.
   * 
   * Use:
   * <code>
   *  $sql = "SELECT name, description FROM products WHERE name like '%:name%';
   *  $filter = ['name'=>'ian',];
   * $result = $dbConn->query($sql, $filter);
   * </code>
   *
   * @param string $query
   * @param array $params
   * @return PDOStatement
   */
  public function query(string $query, array $params = []): PDOStatement
  {
    try {
      $statement = $this->conn->prepare($query);
      foreach ($params as $param => $value) {
        $statement->bindValue(':' . $param, $value);
      }

      $statement->execute();
      return $statement;
    } catch (PDOException $e) {
      throw new Exception("Query failed to execute: {$e->getMessage()}");
    }
  }
}
<?php

/**
 * Router
 *
 * DESCRIPTION OF THE PURPOSE AND USE OF THE CODE
 *
 * Filename:        Router.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

namespace Framework;

use App\Controllers\ErrorController;
use Framework\Middleware\Authorise;

class Router
{
  protected $routes = [];

  public function registerRoute($method, $uri, $action, $middleware = []): void
  {
    list($controller, $controllerMethod) = explode('@', $action);

    $this->routes[] = [
      'method' => $method,
      'uri'    => $uri,
      'controller' => $controller,
      'controllerMethod' => $controllerMethod,
      'Middleware' => $middleware,
    ];
  }

  public function get($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('GET', $uri, $controller, $middleware);
  }

  public function post($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('POST', $uri, $controller, $middleware);
  }

  public function put($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('PUT', $uri, $controller, $middleware);
  }

  public function delete($uri, $controller, $middleware = []): void
  {
    $this->registerRoute('DELETE', $uri, $controller, $middleware);
  }

  public function route($uri): void
  {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    // Check for _method input
    if ($requestMethod == 'POST' && isset($_POST['_method'])) {
      // Override the request method with the value of _method
      $requestMethod = strtoupper($_POST['_method']);
    }
    foreach ($this->routes as $route) {
      // split the current URI into segments
      $uriSegments = explode('/', trim($uri, '/'));

      // split the route URI into segments
      $routeSegments = explode('/', trim($route['uri'], '/'));

      $match = true;

      if (
        count($uriSegments) === count($routeSegments)
        && strtoupper($route['method'] === $requestMethod)
      ) {
        $params = [];

        $match = true;
        $segments = count($uriSegments);
        for ($i = 0; $i < $segments; $i++) {
          // If the uri's do not match and there is no param
          if (
            $routeSegments[$i] !== $uriSegments[$i]
            && !preg_match('/\{(.+?)}/', $routeSegments[$i])
          ) {
            $match = false;
            break;
          }
          // Check for the param and add to $params array
          // eg /products/{id} matched against /products/23454
          if (preg_match('/\{(.+?)}/', $routeSegments[$i], $matches)) {
            $params[$matches[1]] = $uriSegments[$i];
          }
        }
        if ($match) {
          foreach ($route['Middleware'] as $middleware) {
            (new Authorise())->handle($middleware);
          }

          $controller = 'App\\Controllers\\' . $route['controller'];
          $controllerMethod = $route['controllerMethod'];

          // Instantiate the controller and call the method  
          $controllerInstance = new $controller();
          $controllerInstance->$controllerMethod($params);
          return;
        }
      }
    }
    ErrorController::notFound();
  }
}
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
<?php

/**
 * This class contains methods for validating user
 *
 * Filename:        Validation.php
 * Location:        Framework/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    03/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


namespace Framework;

class Validation
{
  /**
   * String validation with minimum and maximum length
   * 
   * @default minimum length is 1
   * @default maximum length is INF
   * @param string $value
   * @param integer $min
   * @param [type] $max
   * @return boolean
   */
  public static function string(string $value, int $min = 1, float $max = INF): bool
  {
    if (is_string($value)) {
      $value = trim($value);
      $length = strlen($value);
      return $length >= $min && $length <= $max;
    }
  }


  /**
   * email validation
   *
   * @param string $value
   * @return boolean
   */
  public static function email(string $value): bool
  {
    $value = trim($value);
    return filter_var($value, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Matching method for two values
   *
   * @param string $value1
   * @param string $value2
   * @return boolean
   */
  public static function match(string $value1, string $value2): bool
  {
    $value1 = trim($value1);
    $value2 = trim($value2);
    return $value1 === $value2;
  }
}
<?php

/**
 * index file to start the application
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
use Framework\Session;

Session::start();

require '../helpers.php';

// Instantiate the router
$router = new Router();

// Get routes
$routes = require basePath('routes.php');

// Get current URI and HTTP method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// echo password_hash("Password1", PASSWORD_DEFAULT);
// die;

// Route the requests
$router->route($uri);
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
  return __DIR__ . '/' . $path;
}

/**
 * Load a view
 *
 * @param string $name
 * @param array $data
 * @return void
 */
function loadView($name, $data = [])
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
function loadPartial($name, $data = []): void
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
<?php

/**
 * This is route file for the Sk-saas-vanilla-mvc
 *
 * Filename:        routes.php
 * Location:        /
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    06/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

$router->get('/', 'HomeController@index');

$router->get('/products', 'ProductController@index');
$router->get('/products/create', 'ProductController@create', ['auth']);
$router->get('/products/edit/{id}', 'ProductController@edit', ['auth']);
$router->get('/products/search', 'ProductController@search');
$router->get('/products/{id}', 'ProductController@show');

$router->post('/products', 'ProductController@store', ['auth']);
$router->put('/products/{id}', 'ProductController@update', ['auth']);
$router->delete('/products/{id}', 'ProductController@destroy', ['auth']);

$router->get('/auth/register', 'UserController@create', ['guest']);
$router->get('/auth/login', 'UserController@login', ['guest']);

$router->post('/auth/register', 'UserController@store', ['guest']);
$router->post('/auth/logout', 'UserController@logout', ['auth']);
$router->post('/auth/login', 'UserController@authenticate', ['guest']);
