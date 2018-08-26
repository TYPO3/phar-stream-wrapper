<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Manager;

class ManagerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Behavior
     */
    private $behaviorProphecy;

    protected function setUp()
    {
        parent::setUp();
        $this->behaviorProphecy = $this->prophesize(Behavior::class);
    }

    protected function tearDown()
    {
        unset($this->behaviorProphecy);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function multipleInitializationFails()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189871);
        Manager::initialize($this->behaviorProphecy->reveal());
        Manager::initialize($this->behaviorProphecy->reveal());
    }

    /**
     * @test
     */
    public function instanceFailsIfNotInitialized()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189872);
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceFailsIfDestroyed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189872);
        Manager::initialize($this->behaviorProphecy->reveal());
        Manager::destroy();
        Manager::instance();
    }

    /**
     * @test
     */
    public function instanceResolvesIfCalledTwice()
    {
        Manager::initialize($this->behaviorProphecy->reveal());
        $firstInstance = Manager::instance();
        $secondInstance = Manager::instance();
        static::assertSame($firstInstance, $secondInstance);
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedToBehavior()
    {
        $testPath = uniqid('path');
        $testCommand = uniqid('command');
        $this->behaviorProphecy->assert($testPath, $testCommand)
            ->willReturn(false)
            ->shouldBeCalled();

        Manager::initialize($this->behaviorProphecy->reveal());

        static::assertFalse(
            Manager::instance()->assert($testPath, $testCommand)
        );
    }
}
