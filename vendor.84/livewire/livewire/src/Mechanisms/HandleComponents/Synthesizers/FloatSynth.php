<?php

namespace Livewire\Mechanisms\HandleComponents\Synthesizers;

// This synth exists solely to capture empty strings being set to float properties...
class FloatSynth extends Synth
{
    public static $key = 'float';

    public static function match($target)
    {
        return false;
    }

    public static function matchByType($type)
    {
        return $type === 'float';
    }

    public static function hydrateFromType($type, $value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if ((float) $value == $value) {
            return (float) $value;
        }

        return $value;
    }
}
