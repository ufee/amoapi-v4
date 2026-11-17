<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

// phpredis 6 объявляет return types, заглушка и phpredis 5 — нет: сигнатуры наследника должны совпасть
if ((new \ReflectionMethod(\Redis::class, 'del'))->hasReturnType()) {
	require __DIR__ . '/CountingRedis/Typed.php';
} else {
	require __DIR__ . '/CountingRedis/Untyped.php';
}
