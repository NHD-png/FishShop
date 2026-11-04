<?php
/** Load project-local .env values without overwriting real process environment variables. */
function fishshop_env(string $name, string $default = ''): string
{
    static $loaded = false;
    static $values = [];

    if (!$loaded) {
        $loaded = true;
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $separator = strpos($line, '=');
                if ($separator === false) continue;
                $key = trim(substr($line, 0, $separator));
                $value = trim(substr($line, $separator + 1));
                if ($key === '') continue;
                if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                    $value = substr($value, 1, -1);
                }
                $values[$key] = $value;
            }
        }
    }

    $environment = getenv($name);
    if ($environment !== false && $environment !== '') return $environment;
    return $values[$name] ?? $default;
}
