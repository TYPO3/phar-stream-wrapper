<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper\Resolver;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class PharInvocationStack
{
    /**
     * @var PharInvocation[]
     */
    private $invocations = [];

    /**
     * @param PharInvocation $invocation
     * @return bool
     */
    public function learn(PharInvocation $invocation): bool
    {
        if ($this->findFirstByBaseName($invocation->getBaseName()) !== null) {
            return false;
        }

        $sameAliasInvocation = $this->findLastByAlias($invocation->getAlias());
        if ($sameAliasInvocation !== null) {
            trigger_error(
                sprintf(
                    'Alias %s cannot be used by %s, used already by %s',
                    $invocation->getAlias(),
                    $invocation->getBaseName(),
                    $sameAliasInvocation->getBaseName()
                ),
                E_USER_WARNING
            );
        }

        $this->invocations[] = $invocation;
        return true;
    }

    /**
     * @param string $baseName
     * @return null|PharInvocation
     */
    public function findFirstByBaseName(string $baseName)
    {
        if ($baseName === '') {
            return null;
        }
        foreach ($this->invocations as $reference) {
            if ($reference->getBaseName() === $baseName) {
                return $reference;
            }
        }
        return null;
    }

    /**
     * @param string $alias
     * @return null|PharInvocation
     */
    public function findLastByAlias(string $alias)
    {
        if ($alias === '') {
            return null;
        }
        foreach (array_reverse($this->invocations) as $reference) {
            if ($reference->getAlias() === $alias) {
                return $reference;
            }
        }
        return null;
    }

    /**
     * @param string $alias
     * @return PharInvocation[]
     */
    public function findAllByAlias(string $alias): array
    {
        if ($alias === '') {
            return [];
        }
        return array_filter(
            $this->invocations,
            function (PharInvocation $reference) use ($alias) {
                return $reference->getAlias() === $alias;
            }
        );
    }
}
