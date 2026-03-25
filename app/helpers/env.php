<?php

function env(string $key, mixed $default = null): mixed
{
    return Config::env($key, $default);
}

function secret(string $key, mixed $default = null): mixed
{
    return Config::secret($key, $default);
}

function required_secret(string $key): mixed
{
    return Config::requireSecret($key);
}