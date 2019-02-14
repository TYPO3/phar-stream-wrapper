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

class AliasReference
{
    /**
     * @var string
     */
    private $baseName;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param string $baseName
     * @param string $alias
     */
    public function __construct(string $baseName, string $alias)
    {
        $this->baseName = $baseName;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->baseName;
    }

    /**
     * @return string
     */
    public function getBaseName(): string
    {
        return $this->baseName;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
