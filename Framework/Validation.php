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
