<?php

namespace DeepCopy;

use function function_exists;

if (function_exists('DeepCopy\deep_copy') === false) {
    /**
     * Deep copies the given value.
     *
     * @param  mixed  $value
     * @param  bool  $useCloneMethod
     * @return mixed
     */
    function deep_copy($value, $useCloneMethod = false)
    {
        return (new DeepCopy($useCloneMethod))->copy($value);
    }
}
