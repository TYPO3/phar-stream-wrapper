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
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;

class PharExtensionInterceptorTest extends TestCase
{
    /**
     * @var string
     */
    private $allowedPath;

    /**
     * @var string
     */
    private $deniedPath;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->allowedPath = __DIR__ . '/../Fixtures/bundle.phar';
        $this->deniedPath = __DIR__ . '/../Fixtures/bundle.phar.png';
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp()
    {
        parent::setUp();

        if (!in_array('phar', stream_get_wrappers())) {
            $this->markTestSkipped('Phar stream wrapper is not registered');
        }

        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', '\TYPO3\PharStreamWrapper\PharStreamWrapper');

        $behavior = new \TYPO3\PharStreamWrapper\Behavior();
        Manager::initialize(
            $behavior->withAssertion(new PharExtensionInterceptor())
        );
    }

    protected function tearDown()
    {
        stream_wrapper_restore('phar');
        Manager::destroy();
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function directoryActionAllowsInvocationDataProvider()
    {
        return array(
            'root directory' => array(
                $this->allowedPath,
                array('Classes', 'Resources')
            ),
            'Classes/Domain/Model directory' => array(
                $this->allowedPath . '/Classes/Domain/Model',
                array('DemoModel.php')
            ),
            'Resources directory' => array(
                $this->allowedPath . '/Resources',
                array('content.txt')
            ),
        );
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
        return array(
            'root directory' => array(
                $this->deniedPath,
                array('Classes', 'Resources')
            ),
            'Classes/Domain/Model directory' => array(
                $this->deniedPath . '/Classes/Domain/Model',
                array('DemoModel.php')
            ),
            'Resources directory' => array(
                $this->deniedPath . '/Resources',
                array('content.txt')
            ),
        );
    }

    /**
     * @param string $path
     *
     * @test
     * @dataProvider directoryActionDeniesInvocationDataProvider
     */
    public function directoryActionDeniesInvocation($path)
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', NULL, 1535198703);
        opendir('phar://' . $path);
    }

    /**
     * @return array
     */
    public function urlStatAllowsInvocationDataProvider()
    {
        return array(
            'filesize base file' => array(
                'filesize',
                $this->allowedPath,
                0, // Phar base file always has zero size when accessed through phar://
            ),
            'filesize Resources/content.txt' => array(
                'filesize',
                $this->allowedPath . '/Resources/content.txt',
                21,
            ),
            'is_file base file' => array(
                'is_file',
                $this->allowedPath,
                false, // Phar base file is not a file when accessed through phar://
            ),
            'is_file Resources/content.txt' => array(
                'is_file',
                $this->allowedPath . '/Resources/content.txt',
                true,
            ),
            'is_dir base file' => array(
                'is_dir',
                $this->allowedPath,
                true, // Phar base file is a directory when accessed through phar://
            ),
            'is_dir Resources/content.txt' => array(
                'is_dir',
                $this->allowedPath . '/Resources/content.txt',
                false,
            ),
            'file_exists base file' => array(
                'file_exists',
                $this->allowedPath,
                true
            ),
            'file_exists Resources/content.txt' => array(
                'file_exists',
                $this->allowedPath . '/Resources/content.txt',
                true
            ),
        );
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
        return array(
            'filesize base file' => array(
                'filesize',
                $this->deniedPath,
                0, // Phar base file always has zero size when accessed through phar://
            ),
            'filesize Resources/content.txt' => array(
                'filesize',
                $this->deniedPath . '/Resources/content.txt',
                21,
            ),
            'is_file base file' => array(
                'is_file',
                $this->deniedPath,
                false, // Phar base file is not a file when accessed through phar://
            ),
            'is_file Resources/content.txt' => array(
                'is_file',
                $this->deniedPath . '/Resources/content.txt',
                true,
            ),
            'is_dir base file' => array(
                'is_dir',
                $this->deniedPath,
                true, // Phar base file is a directory when accessed through phar://
            ),
            'is_dir Resources/content.txt' => array(
                'is_dir',
                $this->deniedPath . '/Resources/content.txt',
                false,
            ),
            'file_exists base file' => array(
                'file_exists',
                $this->deniedPath,
                true
            ),
            'file_exists Resources/content.txt' => array(
                'file_exists',
                $this->deniedPath . '/Resources/content.txt',
                true
            ),
        );
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
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', NULL, 1535198703);
        call_user_func($functionName, 'phar://' . $path);
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForFileOpen()
    {
        $handle = fopen('phar://' . $this->allowedPath . '/Resources/content.txt', 'r');
        self::assertInternalType('resource', $handle);
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForFileRead()
    {
        $handle = fopen('phar://' . $this->allowedPath . '/Resources/content.txt', 'r');
        $content = fread($handle, 1024);
        self::assertSame('TYPO3 demo text file.', $content);
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForFileEnd()
    {
        $handle = fopen('phar://' . $this->allowedPath . '/Resources/content.txt', 'r');
        fread($handle, 1024);
        self::assertTrue(feof($handle));
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForFileClose()
    {
        $handle = fopen('phar://' . $this->allowedPath . '/Resources/content.txt', 'r');
        fclose($handle);
        self::assertFalse(is_resource($handle));
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForFileGetContents()
    {
        $content = file_get_contents('phar://' . $this->allowedPath . '/Resources/content.txt');
        self::assertSame('TYPO3 demo text file.', $content);
    }

    /**
     * @test
     */
    public function streamOpenAllowsInvocationForInclude()
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $this->markTestSkipped('Test requires PHP 5.5 or greater');
        }
        include('phar://' . $this->allowedPath . '/Classes/Domain/Model/DemoModel.php');
        self::assertTrue(
            class_exists(
                '\TYPO3Demo\Demo\Domain\Model\DemoModel',
                false
            )
        );
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForFileOpen()
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', NULL, 1535198703);
        fopen('phar://' . $this->deniedPath . '/Resources/content.txt', 'r');
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForFileGetContents()
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', NULL, 1535198703);
        file_get_contents('phar://' . $this->deniedPath . '/Resources/content.txt');
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForInclude()
    {
        self::setExpectedException('\TYPO3\PharStreamWrapper\Exception', NULL, 1535198703);
        include('phar://' . $this->deniedPath . '/Classes/Domain/Model/DemoModel.php');
    }
}
