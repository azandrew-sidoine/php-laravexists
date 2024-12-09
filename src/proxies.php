<?php


namespace Drewlabs\LaravExists\Proxy;

use Drewlabs\LaravExists\Exists;

/**
 * Creates a exists rules more generic than the existing \Illuminate\Validation\Rules\Exists that
 * support \Closure based search function, An HTTP based query class, etc...
 * 
 * @param string|\Drewlabs\LaravExists\ExistanceVerifier|\Illuminate\Database\Eloquent\Model $table 
 * @param null|string $key 
 * @param string|\Closure|null $mixed The 3rd argument to the function takes a string used as validation message
 *                                               or a projection closure that might be used in case of HTTP Existance verifier
 * @param string|null $message
 * 
 * @return Exists
 * 
 * @throws InvalidArgumentException 
 */
function Exists($table, ?string $key = 'id', $mixed = null, string $message = null)
{
    return Exists::create($table, $key, $mixed, $message);
}
