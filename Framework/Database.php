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
  /**
   * Connection Property
   *
   * @var PDO
   */
  public PDO $conn;

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
