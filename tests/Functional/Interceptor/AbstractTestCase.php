<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Functional\Interceptor;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use TYPO3\PharStreamWrapper\Exception;

class AbstractTestCase extends TestCase
{
    /**
     * @var string[]
     */
    const ALLOWED_PATHS = [];

    /**
     * @var string[]
     */
    const ALLOWED_ALIASED_PATHS = [];

    /**
     * @var string[]
     */
    const DENIED_PATHS = [];

    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 0;

    /**
     * @return array
     */
    public function allowedPathsDataProvider(): array
    {
        return array_combine(
            static::ALLOWED_PATHS,
            array_map([$this, 'wrapInArray'], static::ALLOWED_PATHS)
        );
    }

    /**
     * @return array
     */
    public function allowedAliasedPathsDataProvider(): array
    {
        return array_combine(
            static::ALLOWED_ALIASED_PATHS,
            array_map([$this, 'wrapInArray'], static::ALLOWED_ALIASED_PATHS)
        );
    }

    /**
     * @return array
     */
    public function deniedPathsDataProvider(): array
    {
        return array_combine(
            static::DENIED_PATHS,
            array_map([$this, 'wrapInArray'], static::DENIED_PATHS)
        );
    }

    /**
     * @return array
     */
    public function directoryActionAllowsInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach (static::ALLOWED_PATHS as $allowedPath) {
            $fileName = basename($allowedPath);
            $dataSet = array_merge($dataSet, [
                'root directory ' . $fileName => [
                    $allowedPath,
                    ['Classes', 'Resources']
                ],
                'Classes/Domain/Model directory ' . $fileName => [
                    $allowedPath . '/Classes/Domain/Model',
                    ['DemoModel.php']
                ],
                'Resources directory ' . $fileName => [
                    $allowedPath . '/Resources',
                    ['content.txt', 'exception.php']
                ],
            ]);
        }
        return $dataSet;
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryOpenAllowsInvocation(string $path)
    {
        $handle = opendir('phar://' . $path);
        self::assertInternalType('resource', $handle);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingError()
    {
        if (empty(static::ALLOWED_PATHS) || empty(static::DENIED_PATHS)) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            opendir('phar://' . static::ALLOWED_PATHS[0] . '/__invalid__');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(Warning::class, $throwable);
        }

        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . static::DENIED_PATHS[0]);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingException()
    {
        if (empty(static::ALLOWED_PATHS) || empty(static::DENIED_PATHS)) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            include('phar://' . static::ALLOWED_PATHS[0] . '/Resources/exception.php');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(\RuntimeException::class, $throwable);
            static::assertSame(1539618987, $throwable->getCode());
        }

        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . static::DENIED_PATHS[0]);
    }

    /**
     * @param string $path
     * @param $expectation
     *
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryReadAllowsInvocation(string $path, array $expectation)
    {
        $items = [];
        $handle = opendir('phar://' . $path);
        while (false !== $item = readdir($handle)) {
            $items[] = $item;
        }

        self::assertSame($expectation, $items);
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryCloseAllowsInvocation(string $path)
    {
        $handle = opendir('phar://' . $path);
        closedir($handle);

        self::assertFalse(is_resource($handle));
    }

    /**
     * @return array
     */
    public function directoryActionDeniesInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach (static::DENIED_PATHS as $deniedPath) {
            $fileName = basename($deniedPath);
            $dataSet = array_merge($dataSet, [
                'root directory ' . $fileName => [
                    $deniedPath,
                    ['Classes', 'Resources']
                ],
                'Classes/Domain/Model directory ' . $fileName => [
                    $deniedPath . '/Classes/Domain/Model',
                    ['DemoModel.php']
                ],
                'Resources directory ' . $fileName => [
                    $deniedPath . '/Resources',
                    ['content.txt']
                ],
            ]);
        }
        return $dataSet;
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionDeniesInvocationDataProvider
     */
    public function directoryActionDeniesInvocation(string $path)
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        opendir('phar://' . $path);
    }

    /**
     * @return array
     */
    public function urlStatAllowsInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach (static::ALLOWED_PATHS as $allowedPath) {
            $fileName = basename($allowedPath);
            $dataSet = array_merge($dataSet, [
                'filesize base file ' . $fileName => [
                    'filesize',
                    $allowedPath,
                    0, // Phar base file always has zero size when accessed through phar://
                ],
                'filesize Resources/content.txt ' . $fileName => [
                    'filesize',
                    $allowedPath . '/Resources/content.txt',
                    21,
                ],
                'is_file base file ' . $fileName => [
                    'is_file',
                    $allowedPath,
                    false, // Phar base file is not a file when accessed through phar://
                ],
                'is_file Resources/content.txt ' . $fileName => [
                    'is_file',
                    $allowedPath . '/Resources/content.txt',
                    true,
                ],
                'is_dir base file ' . $fileName => [
                    'is_dir',
                    $allowedPath,
                    true, // Phar base file is a directory when accessed through phar://
                ],
                'is_dir Resources/content.txt ' . $fileName => [
                    'is_dir',
                    $allowedPath . '/Resources/content.txt',
                    false,
                ],
                'file_exists base file ' . $fileName => [
                    'file_exists',
                    $allowedPath,
                    true
                ],
                'file_exists Resources/content.txt ' . $fileName => [
                    'file_exists',
                    $allowedPath . '/Resources/content.txt',
                    true
                ],
            ]);
        }
        return $dataSet;
    }

    /**
     * @param string $functionName
     * @param string $path
     * @param mixed $expectation
     *
     * @test
     * @dataProvider urlStatAllowsInvocationDataProvider
     */
    public function urlStatAllowsInvocation(string $functionName, string $path, $expectation)
    {
        self::assertSame(
            $expectation,
            call_user_func($functionName, 'phar://' . $path)
        );
    }

    /**
     * @return array
     */
    public function urlStatDeniesInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach (static::DENIED_PATHS as $deniedPath) {
            $fileName = basename($deniedPath);
            $dataSet = array_merge($dataSet, [
                'filesize base file ' . $fileName => [
                    'filesize',
                    $deniedPath,
                    0, // Phar base file always has zero size when accessed through phar://
                ],
                'filesize Resources/content.txt' . $fileName => [
                    'filesize',
                    $deniedPath . '/Resources/content.txt',
                    21,
                ],
                'is_file base file' . $fileName => [
                    'is_file',
                    $deniedPath,
                    false, // Phar base file is not a file when accessed through phar://
                ],
                'is_file Resources/content.txt' . $fileName => [
                    'is_file',
                    $deniedPath . '/Resources/content.txt',
                    true,
                ],
                'is_dir base file' . $fileName => [
                    'is_dir',
                    $deniedPath,
                    true, // Phar base file is a directory when accessed through phar://
                ],
                'is_dir Resources/content.txt' . $fileName => [
                    'is_dir',
                    $deniedPath . '/Resources/content.txt',
                    false,
                ],
                'file_exists base file' . $fileName => [
                    'file_exists',
                    $deniedPath,
                    true
                ],
                'file_exists Resources/content.txt' . $fileName => [
                    'file_exists',
                    $deniedPath . '/Resources/content.txt',
                    true
                ],
            ]);
        }
        return $dataSet;
    }

    /**
     * @param string $functionName
     * @param string $path
     * @param mixed $expectation
     *
     * @test
     * @dataProvider urlStatDeniesInvocationDataProvider
     */
    public function urlStatDeniesInvocation(string $functionName, string $path)
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        call_user_func($functionName, 'phar://' . $path);
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileOpen(string $allowedPath)
    {
        $handle = fopen('phar://' . $allowedPath . '/Resources/content.txt', 'r');
        self::assertInternalType('resource', $handle);
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileRead(string $allowedPath)
    {
        $handle = fopen('phar://' . $allowedPath . '/Resources/content.txt', 'r');
        $content = fread($handle, 1024);
        self::assertSame('TYPO3 demo text file.', $content);
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileEnd(string $allowedPath)
    {
        $handle = fopen('phar://' . $allowedPath . '/Resources/content.txt', 'r');
        fread($handle, 1024);
        self::assertTrue(feof($handle));
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileClose(string $allowedPath)
    {
        $handle = fopen('phar://' . $allowedPath . '/Resources/content.txt', 'r');
        fclose($handle);
        self::assertFalse(is_resource($handle));
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileGetContents(string $allowedPath)
    {
        $content = file_get_contents('phar://' . $allowedPath . '/Resources/content.txt');
        self::assertSame('TYPO3 demo text file.', $content);
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForInclude(string $allowedPath)
    {
        include('phar://' . $allowedPath . '/Classes/Domain/Model/DemoModel.php');
        self::assertTrue(
            class_exists(
                \TYPO3Demo\Demo\Domain\Model\DemoModel::class,
                false
            )
        );
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedAliasedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForIncludeOnPhar(string $allowedPath)
    {
        $result = include($allowedPath);
        static::assertNotFalse($result);
    }

    /**
     * @param string $deniedPath
     *
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForFileOpen(string $deniedPath)
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        fopen('phar://' . $deniedPath . '/Resources/content.txt', 'r');
    }

    /**
     * @param string $deniedPath
     *
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForFileGetContents(string $deniedPath)
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_get_contents('phar://' . $deniedPath . '/Resources/content.txt');
    }

    /**
     * @param string $deniedPath
     *
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForInclude(string $deniedPath)
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        include('phar://' . $deniedPath . '/Classes/Domain/Model/DemoModel.php');
    }

    /**
     * @param string $value
     * @return array
     */
    protected function wrapInArray(string $value): array
    {
        return [$value];
    }
}
