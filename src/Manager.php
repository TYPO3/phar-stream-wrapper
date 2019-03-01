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

use TYPO3\PharStreamWrapper\Resolver\PharInvocationResolver;
use TYPO3\PharStreamWrapper\Resolver\PharInvocation;
use TYPO3\PharStreamWrapper\Resolver\PharInvocationCollection;

class Manager
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var Behavior
     */
    private $behavior;

    /**
     * @var Resolvable
     */
    private $resolver;

    /**
     * @var PharInvocationCollection
     */
    private $collection;

    /**
     * @param Behavior $behaviour
     * @param Resolvable $resolver
     * @param PharInvocationCollection $collection
     * @return self
     */
    public static function initialize(
        Behavior $behaviour,
        Resolvable $resolver = null,
        PharInvocationCollection $collection = null
    ): self {
        if (self::$instance === null) {
            self::$instance = new self($behaviour, $resolver, $collection);
            return self::$instance;
        }
        throw new \LogicException(
            'Manager can only be initialized once',
            1535189871
        );
    }

    /**
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        throw new \LogicException(
            'Manager needs to be initialized first',
            1535189872
        );
    }

    /**
     * @return bool
     */
    public static function destroy(): bool
    {
        if (self::$instance === null) {
            return false;
        }
        self::$instance = null;
        return true;
    }

    /**
     * @param Behavior $behaviour
     * @param Resolvable $resolver
     * @param PharInvocationCollection $collection
     */
    private function __construct(
        Behavior $behaviour,
        Resolvable $resolver = null,
        PharInvocationCollection $collection = null
    ) {
        $this->collection = $collection ?? new PharInvocationCollection();
        $this->resolver = $resolver ?? new PharInvocationResolver($this->collection);
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

    /**
     * @param string $path
     * @param null|int $flags
     * @return PharInvocation|null
     */
    public function resolve(string $path, int $flags = null)
    {
        return $this->resolver->resolve($path, $flags);
    }

    /**
     * @param PharInvocation $invocation
     * @param null|int $flags
     */
    public function learnInvocation(PharInvocation $invocation, int $flags = null)
    {
        $this->collection->learn($invocation, $flags);
    }
}
