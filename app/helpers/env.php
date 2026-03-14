<?php

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}