<?php
declare(strict_types = 1);
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
    public function pharPrefixIsRemovedDataProvider(): array
    {
        return [
            ['', ''],
            ['empty', 'empty'],
            ['file.phar', 'file.phar'],
            ['  spaces.phar  ', 'spaces.phar'],
            ['phar://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'],
            ['PHAR://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'],
            ['PhaR://path/file.phar/path/data.txt', 'path/file.phar/path/data.txt'],
            ['  phar://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'],
            ['  PHAR://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'],
            ['  PhaR://path/file.phar/path/data.txt  ', 'path/file.phar/path/data.txt'],
        ];
    }

    /**
     * @test
     * @dataProvider pharPrefixIsRemovedDataProvider
     */
    public function pharPrefixIsRemoved(string $path, string $expectation): void
    {
        static::assertSame(
            $expectation,
            Helper::removePharPrefix($path)
        );
    }

    public function pathIsNormalizedDataProvider(): array
    {
        $dataSet = [
            ['.', '.'],
            ['..', '..'],
            ['./x', './x'],
            ['../x', '../x'],
            ['c:\\a\\b\..\..\x', 'c:/a/b/../../x'],
            ['phar://../x', '../x'],
            ['phar:///../x', '/../x'],
            ['phar://c:\\a\\b\..\..\x', 'c:/a/b/../../x'],
            ['  phar://../x  ', '../x'],
            ['  phar:///../x  ', '/../x'],
            ['  phar://c:\\a\\b\..\..\x  ', 'c:/a/b/../../x'],
        ];

        return array_merge($this->pharPrefixIsRemovedDataProvider(), $dataSet);
    }

    /**
     * @test
     * @dataProvider pathIsNormalizedDataProvider
     */
    public function pathIsNormalized(string $path, string $expectation): void
    {
        static::assertSame(
            $expectation,
            Helper::normalizePath($path)
        );
    }
}
