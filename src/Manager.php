<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class Manager implements Assertable
{
    /**
     * @var static
     */
    private static $instance;

    /**
     * @var Behavior
     */
    private $behavior;

    /**
     * @param Behavior $behaviour
     * @return Manager
     */
    public static function initialize(Behavior $behaviour): self
    {
        if (static::$instance === null) {
            static::$instance = new static($behaviour);
            return static::$instance;
        }
        throw new \LogicException(
            'Manager can only be initialized once',
            1535189871
        );
    }

    /**
     * @return static
     */
    public static function instance(): self
    {
        if (static::$instance !== null) {
            return static::$instance;
        }
        throw new \LogicException(
            'Manager needs to be initialized first',
            1535189872
        );
    }

    public static function destroy()
    {
        if (static::$instance !== null) {
            static::$instance = null;
            return;
        }
        throw new \LogicException(
            'Manager was never initialized',
            1535189873
        );
    }

    /**
     * @param Behavior $behaviour
     */
    private function __construct(Behavior $behaviour)
    {
        $this->behavior = $behaviour;
    }

    /**
     * @param string $path
     * @param string $command
     * @return bool
     */
    public function assert(string $path, string $command): bool
    {
        return $this->behavior->assert($path, $command);
    }
}
