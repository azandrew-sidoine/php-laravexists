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

interface ExistsQueryable
{
    /**
     * Query for value having $column equal to user provided $value.
     *
     * @param string|\Closure $column
     * @param mixed           $value
     *
     * @return self|ExistanceVerifier
     */
    public function where($column, $value = null);

    /**
     * Query for value having $column not equal to user provided $value.
     *
     * @param string|\Closure $column
     * @param mixed           $value
     *
     * @return self|ExistanceVerifier
     */
    public function whereNot($column, $value = null);

    /**
     * Query for value not having column value equals to NULL.
     *
     * @return self|ExistanceVerifier
     */
    public function whereNotNull(string $column);

    /**
     * Query for value having column value equals to NULL.
     *
     * @return self|ExistanceVerifier
     */
    public function whereNull(string $column);
}
