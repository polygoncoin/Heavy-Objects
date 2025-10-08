<?php

/**
 * Heavy Objects
 * php version 7
 *
 * @category  HeavyObjects
 * @package   HeavyObjects
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Heavy-Objects
 * @since     Class available since Release 1.0.0
 */

namespace HeavyObjects;

require_once __DIR__ . '/Autoload.php';

spl_autoload_register(callback: __NAMESPACE__ . '\Autoload::register');
