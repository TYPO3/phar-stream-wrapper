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

class AliasMap
{
    /**
     * @var AliasReference[]
     */
    private $references = [];

    /**
     * @param string $baseName
     * @param string $alias
     * @return bool
     */
    public function append(string $baseName, string $alias): bool
    {
        if ($baseName === '' || $alias === '') {
            return false;
        }
        if ($this->findFirstByBaseName($baseName) !== null) {
            return false;
        }
        $this->references[] = new AliasReference($baseName, $alias);
        return true;
    }

    /**
     * @param string $baseName
     * @return bool
     */
    public function purgeByBaseName(string $baseName): bool
    {
        if ($baseName === '') {
            return false;
        }
        $count = count($this->references);
        $this->references = array_filter(
            $this->references,
            function (AliasReference $reference) use ($baseName) {
                return $reference->getBaseName() !== $baseName;
            }
        );
        return count($this->references) < $count;
    }

    /**
     * @param string $baseName
     * @return null|AliasReference
     */
    public function findFirstByBaseName(string $baseName)
    {
        if ($baseName === '') {
            return null;
        }
        foreach (array_reverse($this->references) as $reference) {
            if ($reference->getBaseName() === $baseName) {
                return $reference;
            }
        }
        return null;
    }

    /**
     * @param string $alias
     * @return null|AliasReference
     */
    public function findLastByAlias(string $alias)
    {
        if ($alias === '') {
            return null;
        }
        foreach (array_reverse($this->references) as $reference) {
            if ($reference->getAlias() === $alias) {
                return $reference;
            }
        }
        return null;
    }

    /**
     * @param string $alias
     * @return AliasReference[]
     */
    public function findAllByAlias(string $alias): array
    {
        if ($alias === '') {
            return [];
        }
        return array_filter(
            $this->references,
            function (AliasReference $reference) use ($alias) {
                return $reference->getAlias() === $alias;
            }
        );
    }
}
