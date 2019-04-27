<?php
/**
 * basically similar to https://github.com/maxmind/GeoIP2-php/releases
 */

\Phar::mapPhar('alias.with.path.phar');
// invoking phar stream wrapper with path of current file
file_exists('phar://' . __FILE__ . '/Classes/Domain/Model/DemoModel.php');
// using internal alias name in order to require file
require('phar://alias.with.path.phar/Classes/Domain/Model/DemoModel.php');
__HALT_COMPILER();