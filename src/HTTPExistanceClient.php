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

use Illuminate\Support\Facades\Http;

class HTTPExistanceClient implements ExistanceVerifier, ExistsQueryable
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var callable
     */
    private $validateResult;

    /**
     * @var array
     */
    private $query = [];

    /**
     * Create an HTTP client verifier instance.
     *
     * @param Closure|callable $validateResult
     *
     * @return void
     */
    public function __construct(string $url, array $headers, callable $validateResult)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->validateResult = $validateResult;
    }

    /**
     * Creates an HTTP existance client instance.
     *
     * @return HTTPExistanceClient
     */
    public static function create(string $url, array $headers = [], ?callable $validateResult = null)
    {
        $callback = $validateResult ?? static function ($result) {
            $values = (array) ($result['data'] ?? []);
            // We simply checks if the return result contains values and is a list values
            return $values === array_filter($values, 'is_array') && count($values) !== 0;
        };

        return new self($url, $headers ?? [], $callback);
    }

    /**
     * Authorize the existance HTTP request with the token.
     *
     * @return self
     */
    public function withBearerToken(?string $token = null)
    {
        $this->headers = array_merge($this->headers ?? [], ['Authorization' => 'Bearer ' . $token]);

        return $this;
    }

    public function where($column, $value = null)
    {
        if (isset($this->query['where'])) {
            $this->query['where'][] = [$column, $value];
        } else {
            $this->query['where'] = [[$column, $value]];
        }

        return $this;
    }

    public function whereNot($column, $value = null)
    {
        if (isset($this->query['where'])) {
            $this->query['where'][] = [$column, '!=', $value];
        } else {
            $this->query['where'] = [[$column, '!=', $value]];
        }

        return $this;
    }

    public function whereNotNull(string $column)
    {
        if (isset($this->query['whereNotNull'])) {
            $this->query['whereNotNull'][] = $column;
        } else {
            $this->query['whereNotNull'] = [$column];
        }

        return $this;
    }

    public function whereNull(string $column)
    {
        if (isset($this->query['whereNull'])) {
            $this->query['whereNull'][] = $column;
        } else {
            $this->query['whereNull'] = [$column];
        }

        return $this;
    }

    public function exists(string $column, $value)
    {
        $response = Http::acceptJson()->withHeaders($this->headers)
            ->get($this->url, !empty($this->query) ? [
                $column => $value,
                '_query' => json_encode($this->query),
            ] : [$column => $value]);
        if (!$response->ok()) {
            return false;
        }
        return ($this->validateResult)(json_decode($response->body(), true), $column, $value);
    }
}
