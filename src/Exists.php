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

use Closure;
use Illuminate\Contracts\Validation\ValidationRule as Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Rules\Exists as RulesExists;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use RuntimeException;

/**
 * @method static where(string|\Closure $column, $value = null)
 * @method static whereNot(string|\Closure $column, $value = null)
 * @method static whereNotNull(string $column)
 * @method static whereNull(string $column)
 */
final class Exists implements Rule, ValidatorAwareRule
{
    use MethodProxy;

    /** @var mixed */
    private $builder;

    /** @var mixed */
    private $table;

    /** @var Validator */
    private $validator;

    /** @var string|null */
    private $column;

    /** @var string */
    private $message;

    /** @var callable */
    private $callback;

    /**
     * Create exists rule instance
     * 
     * @param mixed $table 
     * @param string $column 
     * @param string|\Closure|null $callback         The 3rd argument to the function takes a string used as validation message
     *                                               or a projection closure that might be used in case of HTTP Existance verifier
     * @param string|null $message
     * 
     */
    public function __construct($table, $column = 'id', $callback = null, ?string $message = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->callback = $callback;
        $this->message = is_string($callback) && !function_exists($callback) ? $callback : $message;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->message());
        }
    }

    /**
     * 
     * @param mixed $name 
     * @param mixed $arguments 
     * @return static
     * 
     * @throws LogicException 
     */
    public function __call($name, $arguments)
    {
        $builder = $this->getBuilder();

        if ($builder instanceof \Closure) {
            throw new \LogicException('Closure based query ');
        }

        $this->builder = $this->proxy($this->builder, $name, $arguments);

        return $this;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Query client provider getter.
     *
     * @deprecated
     * 
     * @return \Closure|ExistanceVerifier|RulesExists
     */
    public function getProvider()
    {
        return $this->builder;
    }

    /**
     * Creates a exists rules more generic than the existing \Illuminate\Validation\Rules\Exists that
     * support \Closure based search function, An HTTP based query class, etc...
     * 
     * @param mixed $table 
     * @param null|string $key 
     * @param string|\Closure|null $project The 3rd argument to the function takes a string used as validation message
     *                                               or a projection closure that might be used in case of HTTP Existance verifier
     * @param string|null $message 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public static function create($table, ?string $key = 'id', $project = null, ?string $message = null)
    {
        if (\is_string($table) && self::isValidURL($table)) {
            return new static(
                new HTTPExistanceClient(
                    $table,
                    [],
                    is_string($project) && !function_exists($project) ? null : $project
                ),
                $key,
                $project,
                $message
            );
        }
        if (\is_string($table) && class_exists($table) && is_subclass_of($table, Model::class)) {
            return new static(
                ValidationRule::exists($table, $key),
                $key,
                $project,
                $message
            );
        }

        if (\is_string($table) && !is_a($table, ExistanceVerifier::class, true)) {
            return new static(
                ValidationRule::exists($table, $key),
                $key,
                $project,
                $message
            );
        }

        if (!\is_string($table) && \is_callable($table)) {
            return new static(
                \Closure::fromCallable($table),
                $key,
                $project,
                $message
            );
        }
        /** @var ExistanceVerifier|null */
        $client = null;

        if (\is_object($table) && ($table instanceof ExistanceVerifier)) {
            $client = $table;
        }

        if (null !== $client) {
            return new static($client, $key, $project, $message);
        }

        throw new \InvalidArgumentException('query table is not supported');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $builder = $this->getBuilder();

        if ($builder instanceof RulesExists) {
            $data = [$attribute => $value];
            $parser = new ValidationRuleParser($data);
            $rules = $parser->explode(ValidationRuleParser::filterConditionalRules([$attribute => $builder], $data))->rules;
            $result = ValidationRuleParser::parse($rules[$attribute][0]);

            return $this->validator->validateExists($attribute, $value, $result[1] ?? []);
        }

        if ($builder instanceof ExistanceVerifier) {
            return $builder->exists($this->column, $value);
        }

        if ($builder instanceof Builder || $builder instanceof BaseBuilder) {
            return $builder->where($this->column, $value)->count() !== 0;
        }

        if (is_subclass_of($builder, Builder::class) || is_subclass_of($builder, BaseBuilder::class)) {
            return $builder->where($this->column, $value)->count() !== 0;
        }

        if (is_callable($builder)) {
            return !empty(call_user_func($builder, $attribute, $value)) ? true : false;
        }

        throw new RuntimeException(sprintf("unsupported validation provider type %s", is_object($builder) && !is_null($builder) ? get_class($builder) : gettype($builder)));
    }

    /**
     * Set the message to output on failure
     * 
     * @return static 
     */
    public function withMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message ?? 'The selected :attribute is invalid';
    }

    /**
     * Checks if the provided uri is a valid HTTP url.
     *
     * @param string|\Psr\Http\Message\UriInterface $url
     *
     * @return bool
     */
    private static function isValidURL($url)
    {
        $url = \is_string($url) ? $url : (string) $url;

        return false !== filter_var($url, \FILTER_VALIDATE_URL);
    }

    private function getBuilder()
    {
        if (is_null($this->builder)) {
            $this->builder = 1 === \func_num_args() || ((!\is_string($this->table) && \is_callable($this->table)) || \is_object($this->table)) ? $this->table : (static::isValidURL($this->table) ? new HTTPExistanceClient(rtrim($table ?? '', '/'), [], $this->callback) : ValidationRule::exists($this->table, $this->column));
        }

        return $this->builder;
    }
}
