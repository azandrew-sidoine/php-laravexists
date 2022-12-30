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
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Rules\Exists as RulesExists;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * @method self where(string|\Closure $column, $value = null)
 * @method self whereNot(string|\Closure $column, $value = null)
 * @method self whereNotNull(string $column)
 * @method self whereNull(string $column)
 */
class Exists implements Rule, ValidatorAwareRule
{
    use MethodProxy;

    /**
     * @var \Closure|ExistanceVerifier|RulesExists
     */
    private $queryClient;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var string|null
     */
    private $column;

    /**
     * 
     * @var string
     */
    private $message;

    /**
     * Create exists rule instance
     * 
     * @param mixed $table 
     * @param string $column 
     * @param string|\Closure|null $projectOrMessage The 3rd argument to the function takes a string used as validation message
     *                                               or a projection closure that might be used in case of HTTP Existance verifier
     * @param string|null $message
     * 
     */
    public function __construct($table, $column = 'id', $projectOrMessage = null, string $message = null)
    {
        $this->queryClient = 1 === \func_num_args() || ((!\is_string($table) && \is_callable($table)) || \is_object($table)) ?
            $table : (static::isValidURL($table) ?
                new HTTPExistanceClient(rtrim($table ?? '', '/'), [], $projectOrMessage) :
                ValidationRule::exists($table, $column));
        $this->column = $column;
        // If the projectOrMessage is a string and is not a global function, we use it as the validation message
        $this->message = is_string($projectOrMessage) && !function_exists($projectOrMessage) ? $projectOrMessage : $message;
    }

    public function __call($name, $arguments)
    {
        if ($this->queryClient instanceof \Closure) {
            throw new \LogicException('Closure based query ');
        }
        $this->queryClient = $this->proxy($this->queryClient, $name, $arguments);

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
     * @return \Closure|ExistanceVerifier|RulesExists
     */
    public function getProvider()
    {
        return $this->queryClient;
    }

    /**
     * Creates a exists rules more generic than the existing \Illuminate\Validation\Rules\Exists that
     * support \Closure based search function, An HTTP based query class, etc...
     * 
     * @param mixed $table 
     * @param null|string $key 
     * @param string|\Closure|null $projectOrMessage The 3rd argument to the function takes a string used as validation message
     *                                               or a projection closure that might be used in case of HTTP Existance verifier
     * @param string|null $message 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public static function create($table, ?string $key = 'id', $projectOrMessage = null, string $message = null)
    {
        if (\is_string($table) && self::isValidURL($table)) {
            return new static(
                new HTTPExistanceClient(
                    $table,
                    [],
                    is_string($projectOrMessage) && !function_exists($projectOrMessage) ? null : $projectOrMessage
                ),
                $key,
                $projectOrMessage,
                $message
            );
        }
        if (\is_string($table) && class_exists($table) && is_subclass_of($table, Model::class)) {
            return new static(
                ValidationRule::exists($table, $key),
                $key,
                $projectOrMessage,
                $message
            );
        }
        // This case assume we are building using a table name and a column
        if (\is_string($table) && !is_a($table, ExistanceVerifier::class, true)) {
            return new static(
                ValidationRule::exists($table, $key),
                $key,
                $projectOrMessage,
                $message
            );
        }
        if (!\is_string($table) && \is_callable($table)) {
            return new static(
                \Closure::fromCallable($table),
                $key,
                $projectOrMessage,
                $message
            );
        }
        /**
         * @var ExistanceVerifier
         */
        $queryClient = null;
        if (\is_object($table) && ($table instanceof ExistanceVerifier)) {
            $queryClient = $table;
        }
        if (null !== $queryClient) {
            return new static($queryClient, $key, $projectOrMessage, $message);
        }
        throw new \InvalidArgumentException('Query table is not supported');
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
        if ($this->queryClient instanceof RulesExists) {
            $data = [$attribute => $value];
            $rules = (new ValidationRuleParser($data))
                ->explode(ValidationRuleParser::filterConditionalRules([$attribute => $this->queryClient], $data))
                ->rules;
            $result = ValidationRuleParser::parse($rules[$attribute][0]);

            return $this->validator->validateExists($attribute, $value, $result[1] ?? []);
        }
        if ($this->queryClient instanceof ExistanceVerifier) {
            return $this->queryClient->exists($this->column, $value);
        }

        return !empty(($this->queryClient)($attribute, $value)) ? true : false;
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
        // In case the url is a psr message uri interface
        // we cast the object into PHP string
        $url = \is_string($url) ? $url : (string) $url;

        return false !== filter_var($url, \FILTER_VALIDATE_URL);
    }
}
