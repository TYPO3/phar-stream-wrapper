--TEST--
Test cli phar commands without a .phar extension.
--FILE--
<?php
chdir(__DIR__ . '/Fixtures');
passthru(PHP_BINARY . ' cli_phar');

?>
--EXPECTF--
Can access phar files without .phar extension if they are the CLI command.
Can access phar files with .phar extension.
Cannot access other phar files without .phar extension.
Included a file inside the phar using 'phar://cli_phar/test/file_to_include.php'.
Shutdown functions work in phar files without a .phar extension.
Shutdown functions cannot access other phar files without .phar extension.