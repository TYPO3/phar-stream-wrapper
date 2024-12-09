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
use TYPO3\PharStreamWrapper\Helper;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var string[]
     */
    protected $allowedPaths = [];

    /**
     * @var string[]
     */
    protected $allowedAliasedPaths = [];

    /**
     * @var string[]
     */
    protected $deniedPaths = [];

    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 0;

    public function allowedPathsDataProvider(): array
    {
        return array_combine(
            $this->allowedPaths,
            array_map([$this, 'wrapInArray'], $this->allowedPaths)
        );
    }

    public function allowedAliasedPathsDataProvider(): array
    {
        return array_combine(
            $this->allowedAliasedPaths,
            array_map([$this, 'wrapInArray'], $this->allowedAliasedPaths)
        );
    }

    public function deniedPathsDataProvider(): array
    {
        return array_combine(
            $this->deniedPaths,
            array_map([$this, 'wrapInArray'], $this->deniedPaths)
        );
    }

    public function directoryActionAllowsInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach ($this->allowedPaths as $allowedPath) {
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
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryOpenAllowsInvocation(string $path)
    {
        $handle = opendir('phar://' . $path);
        self::assertIsResource($handle);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingError()
    {
        if ($this->allowedPaths === [] || $this->deniedPaths === []) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            opendir('phar://' . $this->allowedPaths[0] . '/__invalid__');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(Warning::class, $throwable);
        }

        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . $this->deniedPaths[0]);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingException()
    {
        if ($this->allowedPaths === [] || $this->deniedPaths === []) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            include('phar://' . $this->allowedPaths[0] . '/Resources/exception.php');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(\RuntimeException::class, $throwable);
            static::assertSame(1539618987, $throwable->getCode());
        }

        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . $this->deniedPaths[0]);
    }

    /**
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
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryCloseAllowsInvocation(string $path)
    {
        $handle = opendir('phar://' . $path);
        closedir($handle);

        self::assertFalse(is_resource($handle));
    }

    public function directoryActionDeniesInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach ($this->deniedPaths as $deniedPath) {
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
     * @test
     * @dataProvider directoryActionDeniesInvocationDataProvider
     */
    public function directoryActionDeniesInvocation(string $path)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        opendir('phar://' . $path);
    }

    public function urlStatAllowsInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach ($this->allowedPaths as $allowedPath) {
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

    public function urlStatDeniesInvocationDataProvider(): array
    {
        $dataSet = [];
        foreach ($this->deniedPaths as $deniedPath) {
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
     * @test
     * @dataProvider urlStatDeniesInvocationDataProvider
     */
    public function urlStatDeniesInvocation(string $functionName, string $path)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        call_user_func($functionName, 'phar://' . $path);
    }

    /**
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileOpen(string $allowedPath)
    {
        $handle = fopen('phar://' . $allowedPath . '/Resources/content.txt', 'r');
        self::assertIsResource($handle);
    }

    /**
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
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileGetContents(string $allowedPath)
    {
        $content = file_get_contents('phar://' . $allowedPath . '/Resources/content.txt');
        self::assertSame('TYPO3 demo text file.', $content);
    }

    /**
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
     * @test
     * @dataProvider allowedAliasedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForIncludeOnAliasedPhar(string $allowedPath)
    {
        $result = include($allowedPath);
        static::assertNotFalse($result);
    }

    /**
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForFileOpen(string $deniedPath)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        fopen('phar://' . $deniedPath . '/Resources/content.txt', 'r');
    }

    /**
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForFileGetContents(string $deniedPath)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        file_get_contents('phar://' . $deniedPath . '/Resources/content.txt');
    }

    /**
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForInclude(string $deniedPath)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(static::EXPECTED_EXCEPTION_CODE);
        include('phar://' . $deniedPath . '/Classes/Domain/Model/DemoModel.php');
    }

    abstract public function isFileSystemInvocationAcceptableDataProvider(): array;

    /**
     * @test
     * @dataProvider isFileSystemInvocationAcceptableDataProvider
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function isFileSystemInvocationAcceptable(string $path, array $expectation)
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('xdebug not available');
        }

        \xdebug_start_function_monitor(array_keys($expectation));

        include($path);
        new \GeoIp2\Database\Reader(__DIR__ . '/../Fixtures/Resources/GeoLite2/GeoLite2-Country.mmdb');

        \xdebug_stop_function_monitor();
        $invocations = $this->groupInvocations(
            \xdebug_get_monitored_functions(),
            realpath(__DIR__ . '/../../../src')
        );

        self::assertSame($expectation, $invocations);
    }

    protected function groupInvocations(array $monitoredInvocations, string $path): array
    {
        $invocations = [];
        $path = rtrim(Helper::normalizeWindowsPath($path), '/') . '/';
        foreach ($monitoredInvocations as $item) {
            if (empty($item['filename']) || strpos(Helper::normalizeWindowsPath($item['filename']), $path) !== 0) {
                continue;
            }
            $functionName = $item['function'];
            if (isset($invocations[$functionName])) {
                $invocations[$functionName]++;
            } else {
                $invocations[$functionName] = 1;
            }
        }
        return $invocations;
    }

    protected function wrapInArray(string $value): array
    {
        return [$value];
    }

    protected function inflateDataSet(array $items): array
    {
        return array_combine(
            $items,
            array_map([$this, 'wrapInArray'], $items)
        );
    }
}
