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
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

class PharExtensionInterceptorTest extends TestCase
{
    /**
     * @var string
     */
    private $allowedPath = __DIR__ . '/../Fixtures/bundle.phar';

    /**
     * @var string
     */
    private $deniedPath = __DIR__ . '/../Fixtures/bundle.phar.png';

    protected function setUp()
    {
        parent::setUp();

        if (!in_array('phar', stream_get_wrappers())) {
            $this->markTestSkipped('Phar stream wrapper is not registered');
        }

        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', PharStreamWrapper::class);

        Manager::initialize(
            (new \TYPO3\PharStreamWrapper\Behavior())
                ->withAssertion(new PharExtensionInterceptor())
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
    public function directoryActionAllowsInvocationDataProvider(): array
    {
        return [
            'root directory' => [
                $this->allowedPath,
                ['Classes', 'Resources']
            ],
            'Classes/Domain/Model directory' => [
                $this->allowedPath . '/Classes/Domain/Model',
                ['DemoModel.php']
            ],
            'Resources directory' => [
                $this->allowedPath . '/Resources',
                ['content.txt', 'exception.php']
            ],
        ];
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
        try {
            opendir('phar://' . $this->allowedPath . '/__invalid__');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(Warning::class, $throwable);
        }

        self::expectException(Exception::class);
        self::expectExceptionCode(1535198703);
        file_exists('phar://' . $this->deniedPath);
    }

    /**
     * @test
     */
    public function directoryOpenDeniesInvocationAfterCatchingException()
    {
        try {
            include('phar://' . $this->allowedPath . '/Resources/exception.php');
        } catch (\Throwable $throwable) {
            // this possible is caught in user-land code, for these tests
            // it is asserted that it actually happens
            static::assertInstanceOf(\RuntimeException::class, $throwable);
            static::assertSame(1539618987, $throwable->getCode());
        }

        self::expectException(Exception::class);
        self::expectExceptionCode(1535198703);
        file_exists('phar://' . $this->deniedPath);
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
        return [
            'root directory' => [
                $this->deniedPath,
                ['Classes', 'Resources']
            ],
            'Classes/Domain/Model directory' => [
                $this->deniedPath . '/Classes/Domain/Model',
                ['DemoModel.php']
            ],
            'Resources directory' => [
                $this->deniedPath . '/Resources',
                ['content.txt']
            ],
        ];
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
        self::expectExceptionCode(1535198703);
        opendir('phar://' . $path);
    }

    /**
     * @return array
     */
    public function urlStatAllowsInvocationDataProvider(): array
    {
        return [
            'filesize base file' => [
                'filesize',
                $this->allowedPath,
                0, // Phar base file always has zero size when accessed through phar://
            ],
            'filesize Resources/content.txt' => [
                'filesize',
                $this->allowedPath . '/Resources/content.txt',
                21,
            ],
            'is_file base file' => [
                'is_file',
                $this->allowedPath,
                false, // Phar base file is not a file when accessed through phar://
            ],
            'is_file Resources/content.txt' => [
                'is_file',
                $this->allowedPath . '/Resources/content.txt',
                true,
            ],
            'is_dir base file' => [
                'is_dir',
                $this->allowedPath,
                true, // Phar base file is a directory when accessed through phar://
            ],
            'is_dir Resources/content.txt' => [
                'is_dir',
                $this->allowedPath . '/Resources/content.txt',
                false,
            ],
            'file_exists base file' => [
                'file_exists',
                $this->allowedPath,
                true
            ],
            'file_exists Resources/content.txt' => [
                'file_exists',
                $this->allowedPath . '/Resources/content.txt',
                true
            ],
        ];
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
        return [
            'filesize base file' => [
                'filesize',
                $this->deniedPath,
                0, // Phar base file always has zero size when accessed through phar://
            ],
            'filesize Resources/content.txt' => [
                'filesize',
                $this->deniedPath . '/Resources/content.txt',
                21,
            ],
            'is_file base file' => [
                'is_file',
                $this->deniedPath,
                false, // Phar base file is not a file when accessed through phar://
            ],
            'is_file Resources/content.txt' => [
                'is_file',
                $this->deniedPath . '/Resources/content.txt',
                true,
            ],
            'is_dir base file' => [
                'is_dir',
                $this->deniedPath,
                true, // Phar base file is a directory when accessed through phar://
            ],
            'is_dir Resources/content.txt' => [
                'is_dir',
                $this->deniedPath . '/Resources/content.txt',
                false,
            ],
            'file_exists base file' => [
                'file_exists',
                $this->deniedPath,
                true
            ],
            'file_exists Resources/content.txt' => [
                'file_exists',
                $this->deniedPath . '/Resources/content.txt',
                true
            ],
        ];
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
        self::expectExceptionCode(1535198703);
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
        include('phar://' . $this->allowedPath . '/Classes/Domain/Model/DemoModel.php');
        self::assertTrue(
            class_exists(
                \TYPO3Demo\Demo\Domain\Model\DemoModel::class,
                false
            )
        );
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForFileOpen()
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1535198703);
        fopen('phar://' . $this->deniedPath . '/Resources/content.txt', 'r');
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForFileGetContents()
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1535198703);
        file_get_contents('phar://' . $this->deniedPath . '/Resources/content.txt');
    }

    /**
     * @test
     */
    public function streamOpenDeniesInvocationForInclude()
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1535198703);
        include('phar://' . $this->deniedPath . '/Classes/Domain/Model/DemoModel.php');
    }
}
