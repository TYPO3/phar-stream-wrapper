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

use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Phar\Reader;
use TYPO3\PharStreamWrapper\Resolvable;

class BaseNameResolver implements Resolvable
{
    const RESOLVE_REALPATH = 1;
    const RESOLVE_ALIAS = 2;

    /**
     * @var string[]
     */
    private $aliasMap = [];

    /**
     * @param string $path
     * @param int|null $flags
     * @return null|string
     */
    public function resolveBaseName(string $path, int $flags = null)
    {
        $hasPharPrefix = Helper::hasPharPrefix($path);
        $flags = $flags ?? static::RESOLVE_REALPATH | static::RESOLVE_ALIAS;

        if ($hasPharPrefix && $flags & static::RESOLVE_ALIAS) {
            $baseNameFromAliasMap = $this->resolveBaseNameFromAliasMap($path);
            if ($baseNameFromAliasMap !== null) {
                return $baseNameFromAliasMap;
            }
        }

        $baseName = Helper::determineBaseFile($path);
        if ($baseName !== null && $flags & static::RESOLVE_REALPATH) {
            $baseName = realpath($baseName);
        }
        if ($baseName !== null && $hasPharPrefix && $flags & static::RESOLVE_ALIAS) {
            $this->learnAlias($baseName);
        }

        return $baseName;
    }

    /**
     * @param string $path
     * @return null|string
     */
    private function resolveBaseNameFromAliasMap(string $path)
    {
        $normalizedBaseName = Helper::normalizePath($path);
        $possibleAlias = strstr($normalizedBaseName, '/', true);
        return $this->getAlias($possibleAlias ?: '');
    }


    /**
     * @param string $basePath
     */
    private function learnAlias(string $basePath)
    {
        $alias = (new Reader($basePath))->resolveContainer()->getAlias();
        if ($alias !== '' && $alias !== $basePath) {
            $this->setAlias($alias, $basePath);
        }
    }

    /**
     * @param string $alias
     * @return null|string
     */
    private function getAlias(string $alias)
    {
        return $this->aliasMap[$alias] ?? null;
    }

    /**
     * @param string $alias
     * @param string $basePath
     */
    private function setAlias(string $alias, string $basePath)
    {
        $currentBasePath = $this->getAlias($alias);
        if ($currentBasePath !== null && $currentBasePath !== $basePath) {
            throw new \TYPO3\PharStreamWrapper\Exception(
                sprintf(
                    'Alias %s already registered for different path %s',
                    $alias,
                    $currentBasePath
                ),
                1547893171
            );
        }
        $this->aliasMap[$alias] = $basePath;
    }
}
