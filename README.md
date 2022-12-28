# Laravexists

`Laravexists` package provides developper with utility and classes that calls `exists` rule on data source. It internally use the laravel `Rule::exists()` or an existance verifier class that must provides implementation that check data existance on a given data source.

## Installation

To install the library in a PHP project, use the composer package manager:

> composer require drewlabs/laravexists

The command above install the library and all it dependencies.

## Usage

- Using the default laravel exists rule

The library can be used as a drop-in replacement for Laravel default `Rule::exists()` validation rule as it support it internally.

```php
use Drewlabs\LaravExists\Exists;

//...

class MyRequest extends FormRequest {

    public function rules()
    {
        return [
            // ... validation rules
            'post_id' => [new Exists('posts', 'id')]
            // Or using the factory function
            'post_id' => [Exists::create('posts', 'id')]
        ]
    }
}
```

- Using an HTTP existance verifier

The library comes with an HTTP existance verifier with can be used as a factory instance for verifying existance of a data using REST interface. It's kind of the main purpose of this library as it allow to check if a given data exist on a given server remoetly.
The library relies on query parameter do send query that filters the result from the the HTTP server (if supported by the server).

```php

// Import the exist validation rule
use Drewlabs\LaravExists\Exists;
// Import the http existance client class
use Drewlabs\LaravExists\HTTPExistanceClient;

//...

class MyRequest extends FormRequest {

    public function rules()
    {
        return [
            // ... validation rules
            'post_id' => [
                Exists::create(
                    HTTPExistanceClient::create(
                        'http://localhost:3000/api/posts',
                    )->withBearerToken($this->bearerToken()),
                    // The attribute used for check on the ressult from the HTTP server
                    'id'
                )
            ]
        ]
    }
}
```

**Note** By default the `HTTPExistanceClient` class uses an internal callback that filter and validate the entry `data` of the server json response. To override the default implementation:

```php
// Import the http existance client class
use Drewlabs\LaravExists\HTTPExistanceClient;

// ...
HTTPExistanceClient::create(
    '<RESOURCE_URL>',
    [], // Http headers
    // The response is a PHP object or array (dictionary)
    // $key is the column or key passed to the Exists construction
    // $value is the value provided by the user
    function($response, $key, $value) {
        // TODO, check if the response contains the data
        return true; // true or false base on the result of the previous step
    }
)
// ...
```

- Custom verifier

The library comes with an interface that can be implemented to provide a custom existance, verifier:

```php
use Drewlabs\LaravExists\ExistanceVerifier;

class MyCustomVerifier implements ExistanceVerifier
{
    public function exists(string $column, $value)
    {
        // TODO: Provide existance verification implementation
    }
}
```

**Note** The library is still under development as the API might change.
