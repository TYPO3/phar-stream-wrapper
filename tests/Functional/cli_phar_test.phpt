--TEST--
Test cli phar commands without a .phar extension.
--FILE--
<?php
chdir(__DIR__ . '/Fixtures');
passthru(PHP_BINARY . ' cli_phar');

?>
--EXPECTF--
File exists!