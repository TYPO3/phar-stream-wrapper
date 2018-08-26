<?php
declare(strict_types = 1);
namespace TYPO3\PharStreamWrapper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Behavior;

class BehaviourTest extends TestCase
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Assertable|ObjectProphecy
     */
    private $allAssertion;

    /**
     * @var Assertable|ObjectProphecy
     */
    private $specificAssertion;


    protected function setUp()
    {
        parent::setUp();
        $this->path = uniqid('path');
        $this->allAssertion = $this->prophesize(Assertable::class);
        $this->specificAssertion = $this->prophesize(Assertable::class);
    }

    protected function tearDown()
    {
        unset($this->path, $this->allAssertion, $this->specificAssertion);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function assertionAssignmentFailsWithUnknownCommand()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189881);
        (new Behavior())->withAssertion(
            $this->allAssertion->reveal(),
            'UNKNOWN'
        );
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithInvalidCommand()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189882);
        $subject = (new Behavior())->withAssertion(
            $this->allAssertion->reveal()
        );
        $subject->assert($this->path, 'UNKNOWN');
    }

    /**
     * @test
     */
    public function assertInvocationFailsWithIncompleteAssertions()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1535189883);
        $subject = (new Behavior())->withAssertion(
            $this->allAssertion->reveal(),
            Behavior::COMMAND_UNLINK
        );
        $subject->assert($this->path, Behavior::COMMAND_UNLINK);
    }

    /**
     * @test
     */
    public function assertInvocationIsDelegatedWithEmptyCommands()
    {
        $commands = [
            Behavior::COMMAND_DIR_OPENDIR,
            Behavior::COMMAND_MKDIR,
            Behavior::COMMAND_RENAME,
            Behavior::COMMAND_RMDIR,
            Behavior::COMMAND_STEAM_METADATA,
            Behavior::COMMAND_STREAM_OPEN,
            Behavior::COMMAND_UNLINK,
            Behavior::COMMAND_URL_STAT,
        ];
        foreach ($commands as $command) {
            $this->allAssertion->assert($this->path, $command)
                ->willReturn(false)
                ->shouldBeCalled();
        }

        $subject = (new Behavior())->withAssertion(
            $this->allAssertion->reveal()
        );

        foreach ($commands as $command) {
            static::assertFalse(
                $subject->assert($this->path, $command)
            );
        }
    }

    /**
     * @test
     */
    public function assertionSucceedsWithEmptyAndSingleCommands()
    {
        $this->allAssertion
            ->assert($this->path, Behavior::COMMAND_URL_STAT)
            ->willReturn(false)
            ->shouldBeCalled();
        $this->specificAssertion
            ->assert($this->path, Behavior::COMMAND_UNLINK)
            ->willReturn(false)
            ->shouldBeCalled();

        $subject = (new Behavior())
            ->withAssertion(
                $this->allAssertion->reveal()
            )
            ->withAssertion(
                $this->specificAssertion->reveal(),
                Behavior::COMMAND_UNLINK
            );

        static::assertFalse(
            $subject->assert($this->path, Behavior::COMMAND_URL_STAT)
        );
        static::assertFalse(
            $subject->assert($this->path, Behavior::COMMAND_UNLINK)
        );
    }
}
