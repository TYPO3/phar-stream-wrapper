<?php
namespace TYPO3\PharStreamWrapper\Tests\Unit;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PHPUnit\Framework\TestCase;
use TYPO3\PharStreamWrapper\Helper;

class HelperTest extends TestCase
{
    /**
     * @return array
     */
    public function pharPrefixIsRemovedDataProvider()
    {
        return array(
            array('', ''),
            array('empty', 'empty'),
            array('file.phar', 'file.phar'),
            array('  spaces.phar  ', 'spaces.phar'),
            array('phar://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'),
            array('PHAR://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'),
            array('PhaR://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'),
            array('  phar://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'),
            array('  PHAR://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'),
            array('  PhaR://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'),
        );
    }

    /**
     * @param string $path
     * @param string $expectation
     *
     * @test
     * @dataProvider pharPrefixIsRemovedDataProvider
     */
    public function pharPrefixIsRemoved($path, $expectation)
    {
        static::assertSame(
            $expectation,
            Helper::removePharPrefix($path)
        );
    }

    /**
     * @return array
     */
    public function pathIsNormalizedDataProvider()
    {
        $dataSet = array(
            array('.', '.'),
            array('..', '..'),
            array('./x', './x'),
            array('../x', '../x'),
            array('c:\\a\\b\..\..\x', 'c:/a/b/../../x'),
            array('phar://../x', '../x'),
            array('phar:///../x', '/../x'),
            array('phar://c:\\a\\b\..\..\x', 'c:/a/b/../../x'),
            array('  phar://../x  ', '../x'),
            array('  phar:///../x  ', '/../x'),
            array('  phar://c:\\a\\b\..\..\x  ', 'c:/a/b/../../x'),
        );

        return array_merge($this->pharPrefixIsRemovedDataProvider(), $dataSet);
    }

    /**
     * @param string $path
     * @param string $expectation
     *
     * @test
     * @dataProvider pathIsNormalizedDataProvider
     */
    public function pathIsNormalized($path, $expectation)
    {
        static::assertSame(
            $expectation,
            Helper::normalizePath($path)
        );
    }
}
