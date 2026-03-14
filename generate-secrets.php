<?php

echo 'APP_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;
echo 'CSRF_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;