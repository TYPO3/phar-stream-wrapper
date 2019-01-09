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