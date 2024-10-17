<?php

namespace App\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class TrimmingEnvVarProcessor implements EnvVarProcessorInterface
{

    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        return trim($getEnv($name));
    }

    public static function getProvidedTypes(): array
    {
        return ['trim' => 'string'];
    }
}