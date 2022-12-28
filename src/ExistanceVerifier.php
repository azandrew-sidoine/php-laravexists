<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\LaravExists;

interface ExistanceVerifier
{
    /**
     * Check if an entry matching the provided value for the $column key exist in the data store.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function exists(string $column, $value);
}
