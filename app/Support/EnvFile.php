<?php

namespace App\Support;

use RuntimeException;

/**
 * Sets KEY=value lines in a .env file: replaces the first line for a key
 * (and drops later duplicates), or appends it.
 */
class EnvFile
{
    /**
     * @param  array<string, string>  $values
     */
    public static function set(string $path, array $values): void
    {
        $contents = is_file($path) ? (string) file_get_contents($path) : '';
        $lines = $contents === '' ? [] : preg_split('/\r\n|\n|\r/', rtrim($contents, "\r\n"));
        $lines = is_array($lines) ? $lines : [];

        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || preg_match('/[\s"\'#]/', $value)) {
                throw new RuntimeException("Refusing to write an unsafe .env value for {$key}.");
            }

            $found = false;

            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line)) {
                    if ($found) {
                        unset($lines[$i]);
                    } else {
                        $lines[$i] = "{$key}={$value}";
                        $found = true;
                    }
                }
            }

            if (! $found) {
                $lines[] = "{$key}={$value}";
            }
        }

        if (file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL) === false) {
            throw new RuntimeException("Could not write {$path}.");
        }
    }
}
