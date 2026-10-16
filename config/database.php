<?php
/**
 * config/database.php
 * -------------------------------------------------------------
 * Database settings and connection helper for the SSIS prototype.
 * Change the settings below ONLY here. Other files call getConnection().
 * -------------------------------------------------------------
 */

// ----- SETTINGS (XAMPP defaults) -----
define('DB_HOST', 'localhost');
define('DB_NAME', 'cuyotech_ssis');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP's default root password is empty
define('DB_CHARSET', 'utf8mb4');

/**
 * Creates and returns a PDO connection to the SSIS database.
 * Throws a PDOException if the connection fails.
 */
function getConnection(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // report errors as exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // return rows as arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}
