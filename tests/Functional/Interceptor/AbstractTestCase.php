<?php
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

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{

    /**
     * @var int
     */
    const EXPECTED_EXCEPTION_CODE = 0;

    /**
     * @var string[]
     */
    protected $allowedPaths = array();

    /**
     * @var string[]
     */
    protected $allowedAliasedPaths = array();

    /**
     * @var string[]
     */
    protected $deniedPaths = array();

    /**
     * @return array
     */
    public function allowedPathsDataProvider()
    {
        return array_combine(
            $this->allowedPaths,
            array_map(array($this, 'wrapInArray'), $this->allowedPaths)
        );
    }

    /**
     * @return array
     */
    public function allowedAliasedPathsDataProvider()
    {
        return array_combine(
            $this->allowedAliasedPaths,
            array_map(array($this, 'wrapInArray'), $this->allowedAliasedPaths)
        );
    }

    /**
     * @return array
     */
    public function deniedPathsDataProvider()
    {
        return array_combine(
            $this->deniedPaths,
            array_map(array($this, 'wrapInArray'), $this->deniedPaths)
        );
    }

    /**
     * @return array
     */
    public function directoryActionAllowsInvocationDataProvider()
    {
        $dataSet = array();
        foreach ($this->allowedPaths as $allowedPath) {
            $fileName = basename($allowedPath);
            $dataSet = array_merge($dataSet, array(
                'root directory ' . $fileName => array(
                    $allowedPath,
                    array('Classes', 'Resources')
                ),
                'Classes/Domain/Model directory ' . $fileName => array(
                    $allowedPath . '/Classes/Domain/Model',
                    array('DemoModel.php')
                ),
                'Resources directory ' . $fileName => array(
                    $allowedPath . '/Resources',
                    array('content.txt', 'exception.php')
                ),
            ));
        }
        return $dataSet;
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryOpenAllowsInvocation($path)
    {
        $handle = opendir('phar://' . $path);
        self::assertInternalType('resource', $handle);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingError()
    {
        if (empty($this->allowedPaths) || empty($this->deniedPaths)) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            opendir('phar://' . $this->allowedPaths[0] . '/__invalid__');
        } catch (\PHPUnit_Framework_Error_Warning $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf('\PHPUnit_Framework_Error_Warning', $throwable);
        }

        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . $this->deniedPaths[0]);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingException()
    {
        if (empty($this->allowedPaths) || empty($this->deniedPaths)) {
            $this->markTestSkipped('No ALLOWED_PATHS and DENIED_PATHS defined');
        }
        try {
            include('phar://' . $this->allowedPaths[0] . '/Resources/exception.php');
        } catch (\RuntimeException $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf('\RuntimeException', $throwable);
            static::assertSame(1539618987, $throwable->getCode());
        }

        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        file_exists('phar://' . $this->deniedPaths[0]);
    }

    /**
     * @param string $path
     * @param $expectation
     *
     * @test
     * @dataProvider directoryActionAllowsInvocationDataProvider
     */
    public function directoryReadAllowsInvocation($path, array $expectation)
    {
        $items = array();
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
    public function directoryCloseAllowsInvocation($path)
    {
        $handle = opendir('phar://' . $path);
        closedir($handle);

        self::assertFalse(is_resource($handle));
    }

    /**
     * @return array
     */
    public function directoryActionDeniesInvocationDataProvider()
    {
        $dataSet = array();
        foreach ($this->deniedPaths as $deniedPath) {
            $fileName = basename($deniedPath);
            $dataSet = array_merge($dataSet, array(
                'root directory ' . $fileName => array(
                    $deniedPath,
                    array('Classes', 'Resources')
                ),
                'Classes/Domain/Model directory ' . $fileName => array(
                    $deniedPath . '/Classes/Domain/Model',
                    array('DemoModel.php')
                ),
                'Resources directory ' . $fileName => array(
                    $deniedPath . '/Resources',
                    array('content.txt')
                ),
            ));
        }
        return $dataSet;
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionDeniesInvocationDataProvider
     */
    public function directoryActionDeniesInvocation($path)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        opendir('phar://' . $path);
    }

    /**
     * @return array
     */
    public function urlStatAllowsInvocationDataProvider()
    {
        $dataSet = array();
        foreach ($this->allowedPaths as $allowedPath) {
            $fileName = basename($allowedPath);
            $dataSet = array_merge($dataSet, array(
                'filesize base file ' . $fileName => array(
                    'filesize',
                    $allowedPath,
                    0, // Phar base file always has zero size when accessed through phar://
                ),
                'filesize Resources/content.txt ' . $fileName => array(
                    'filesize',
                    $allowedPath . '/Resources/content.txt',
                    21,
                ),
                'is_file base file ' . $fileName => array(
                    'is_file',
                    $allowedPath,
                    false, // Phar base file is not a file when accessed through phar://
                ),
                'is_file Resources/content.txt ' . $fileName => array(
                    'is_file',
                    $allowedPath . '/Resources/content.txt',
                    true,
                ),
                'is_dir base file ' . $fileName => array(
                    'is_dir',
                    $allowedPath,
                    true, // Phar base file is a directory when accessed through phar://
                ),
                'is_dir Resources/content.txt ' . $fileName => array(
                    'is_dir',
                    $allowedPath . '/Resources/content.txt',
                    false,
                ),
                'file_exists base file ' . $fileName => array(
                    'file_exists',
                    $allowedPath,
                    true
                ),
                'file_exists Resources/content.txt ' . $fileName => array(
                    'file_exists',
                    $allowedPath . '/Resources/content.txt',
                    true
                ),
            ));
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
    public function urlStatAllowsInvocation($functionName, $path, $expectation)
    {
        self::assertSame(
            $expectation,
            call_user_func($functionName, 'phar://' . $path)
        );
    }

    /**
     * @return array
     */
    public function urlStatDeniesInvocationDataProvider()
    {
        $dataSet = array();
        foreach ($this->deniedPaths as $deniedPath) {
            $fileName = basename($deniedPath);
            $dataSet = array_merge($dataSet, array(
                'filesize base file ' . $fileName => array(
                    'filesize',
                    $deniedPath,
                    0, // Phar base file always has zero size when accessed through phar://
                ),
                'filesize Resources/content.txt' . $fileName => array(
                    'filesize',
                    $deniedPath . '/Resources/content.txt',
                    21,
                ),
                'is_file base file' . $fileName => array(
                    'is_file',
                    $deniedPath,
                    false, // Phar base file is not a file when accessed through phar://
                ),
                'is_file Resources/content.txt' . $fileName => array(
                    'is_file',
                    $deniedPath . '/Resources/content.txt',
                    true,
                ),
                'is_dir base file' . $fileName => array(
                    'is_dir',
                    $deniedPath,
                    true, // Phar base file is a directory when accessed through phar://
                ),
                'is_dir Resources/content.txt' . $fileName => array(
                    'is_dir',
                    $deniedPath . '/Resources/content.txt',
                    false,
                ),
                'file_exists base file' . $fileName => array(
                    'file_exists',
                    $deniedPath,
                    true
                ),
                'file_exists Resources/content.txt' . $fileName => array(
                    'file_exists',
                    $deniedPath . '/Resources/content.txt',
                    true
                ),
            ));
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
    public function urlStatDeniesInvocation($functionName, $path)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        call_user_func($functionName, 'phar://' . $path);
    }

    /**
     * @param string $allowedPath
     *
     * @test
     * @dataProvider allowedPathsDataProvider
     */
    public function streamOpenAllowsInvocationForFileOpen($allowedPath)
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
    public function streamOpenAllowsInvocationForFileRead($allowedPath)
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
    public function streamOpenAllowsInvocationForFileEnd($allowedPath)
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
    public function streamOpenAllowsInvocationForFileClose($allowedPath)
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
    public function streamOpenAllowsInvocationForFileGetContents($allowedPath)
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
    public function streamOpenAllowsInvocationForInclude($allowedPath)
    {
        include('phar://' . $allowedPath . '/Classes/Domain/Model/DemoModel.php');
        self::assertTrue(
            class_exists(
                '\TYPO3Demo\Demo\Domain\Model\DemoModel',
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
    public function streamOpenAllowsInvocationForIncludeOnAliasedPhar($allowedPath)
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
    public function streamOpenDeniesInvocationForFileOpen($deniedPath)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        fopen('phar://' . $deniedPath . '/Resources/content.txt', 'r');
    }

    /**
     * @param string $deniedPath
     *
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForFileGetContents($deniedPath)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        file_get_contents('phar://' . $deniedPath . '/Resources/content.txt');
    }

    /**
     * @param string $deniedPath
     *
     * @test
     * @dataProvider deniedPathsDataProvider
     */
    public function streamOpenDeniesInvocationForInclude($deniedPath)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', '', static::EXPECTED_EXCEPTION_CODE);
        include('phar://' . $deniedPath . '/Classes/Domain/Model/DemoModel.php');
    }

    /**
     * @return array
     */
    abstract public function isFileSystemInvocationAcceptableDataProvider();

    /**
     * @param string $path
     * @param array $expectation
     *
     * @test
     * @dataProvider isFileSystemInvocationAcceptableDataProvider
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function isFileSystemInvocationAcceptable($path, array $expectation)
    {
        if (!extension_loaded('xdebug')
            || version_compare(phpversion('xdebug'), '2.4.0', '<')
        ) {
            $this->markTestSkipped('xdebug not available (>=2.4.0)');
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

    /**
     * @param array $monitoredInvocations
     * @param string $path
     * @return array
     */
    protected function groupInvocations(array $monitoredInvocations, $path)
    {
        $invocations = array();
        $path = rtrim($path, '/') . '/';
        foreach ($monitoredInvocations as $item) {
            if (empty($item['filename']) || strpos($item['filename'], $path) !== 0) {
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

    /**
     * @param string $value
     * @return array
     */
    protected function wrapInArray($value)
    {
        return array($value);
    }

    /**
     * @param array $items
     * @return array
     */
    protected function inflateDataSet(array $items)
    {
        return array_combine(
            $items,
            array_map(array($this, 'wrapInArray'), $items)
        );
    }
}
