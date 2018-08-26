<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TYPO3\PharStreamWrapper\Helper;

class HelperTest extends TestCase
{
    /**
     * @return array
     */
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
     * @param string $path
     * @param string $expectation
     *
     * @test
     * @dataProvider pharPrefixIsRemovedDataProvider
     */
    public function pharPrefixIsRemoved(string $path, string $expectation)
    {
        static::assertSame(
            $expectation,
            Helper::removePharPrefix($path)
        );
    }

    /**
     * @return array
     */
    public function pathIsNormalizedDataProvider(): array
    {
        $dataSet = [
            ['.', ''],
            ['..', ''],
            ['../x', 'x'],
            ['./././x', 'x'],
            ['./.././../x', 'x'],
            ['a/../x', 'x'],
            ['a/b/../../x', 'x'],
            ['/a/b/../../x', '/x'],
            ['c:\\a\\b\..\..\x', 'c:/x'],
            ['phar://../x', 'x'],
            ['phar://../x/file.phar', 'x/file.phar'],
            ['phar:///../x/file.phar', '/x/file.phar'],
            ['phar://a/b/../../x', 'x'],
            ['phar:///a/b/../../x', '/x'],
            ['phar://a/b/../../x/file.phar', 'x/file.phar'],
            ['phar:///a/b/../../x/file.phar', '/x/file.phar'],
            ['phar://c:\\a\\b\\..\\..\\x\\file.phar', 'c:/x/file.phar'],
            ['  phar:///a/b/../../x/file.phar  ', '/x/file.phar'],
        ];

        return array_merge($this->pharPrefixIsRemovedDataProvider(), $dataSet);
    }

    /**
     * @param string $path
     * @param string $expectation
     *
     * @test
     * @dataProvider pathIsNormalizedDataProvider
     */
    public function pathIsNormalized(string $path, string $expectation)
    {
        static::assertSame(
            $expectation,
            Helper::normalizePath($path)
        );
    }
}
