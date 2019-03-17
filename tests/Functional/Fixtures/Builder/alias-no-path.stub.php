#!/usr/bin/php
<?php
/**
 * basically similar to https://github.com/aws/aws-sdk-php/releases
 */

\Phar::mapPhar('alias.no.path.phar');
// using internal alias name in order to require file
require('phar://alias.no.path.phar/Classes/Domain/Model/DemoModel.php');
__HALT_COMPILER();