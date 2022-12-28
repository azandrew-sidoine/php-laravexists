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

trait MethodProxy
{
    /**
     * Proxy call to user provided object.
     *
     * @param mixed $object
     * @param mixed $method
     * @param array $args
     *
     * @throws \Error
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function proxy($object, $method, $args = [], ?\Closure $default = null)
    {
        try {
            // Call the method on the provided object
            return $object->{$method}(...$args);
        } catch (\Error|\BadMethodCallException $e) {
            // Call the default method if the specified method does not exits
            if ((null !== $default) && \is_callable($default)) {
                return $default(...$args);
            }
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';
            if (!preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }
            if (
                $matches['class'] !== \get_class($object) ||
                $matches['method'] !== $method
            ) {
                throw $e;
            }
            throw new \BadMethodCallException(
                sprintf(
                    'Call to undefined method %s::%s()',
                    static::class,
                    $method
                )
            );
        }
    }

    /**
     * Provide a dynamic method call interface to the current object.
     * if the specified method does not exists, the default method is called
     * instead.
     *
     * @param mixed    $method
     * @param array    $args
     * @param \Closure $default
     *
     * @return mixed
     */
    public function call($method, $args = [], ?\Closure $default = null)
    {
        if ($method instanceof \Closure) {
            try {
                return (new \ReflectionFunction($method))->invoke(...$args);
            } catch (\Error|\BadMethodCallException|\ReflectionException $e) {
                // Call the default method if the specified method does not exits
                if ((null !== $default) && \is_callable($default)) {
                    return $default(...$args);
                }
            }
        }

        return $this->proxy($this, $method, $args, $default);
    }
}
