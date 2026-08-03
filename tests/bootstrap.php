<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Support/redis_stub.php';
require __DIR__ . '/Support/mongodb_stub.php';

/**
 * Загружает KEY=VALUE из .env, не перезаписывая уже заданные переменные окружения.
 */
$loadEnvFile = static function (string $path): void {
	if (!is_file($path) || !is_readable($path)) {
		return;
	}

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		return;
	}

	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (strpos($line, '=') === false) {
			continue;
		}

		[$name, $value] = explode('=', $line, 2);
		$name = trim($name);
		$value = trim($value);

		if ($name === '') {
			continue;
		}

		// Уже задано в окружении / CI — не трогаем
		$existing = getenv($name);
		if ($existing !== false && $existing !== '') {
			continue;
		}

		if (
			(strlen($value) >= 2)
			&& (
				($value[0] === '"' && substr($value, -1) === '"')
				|| ($value[0] === "'" && substr($value, -1) === "'")
			)
		) {
			$value = substr($value, 1, -1);
		}

		putenv($name . '=' . $value);
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}
};

$root = dirname(__DIR__);
$loadEnvFile($root . '/.env');
