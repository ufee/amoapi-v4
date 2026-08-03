<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

/**
 * Локальный php -S для unit-тестов реального Query::execute / curl.
 */
final class LocalHttpServer
{
	/** @var resource|false */
	private $process;

	/** @var array<int, resource> */
	private $pipes = [];

	/** @var string */
	private $script;

	/** @var int */
	public $port;

	public function __construct()
	{
		$this->port = random_int(19000, 29000);
		$this->script = sys_get_temp_dir() . '/amoapi-http-' . uniqid('', true) . '.php';
		file_put_contents($this->script, <<<'PHP'
<?php
$code = (int) ($_SERVER['HTTP_X_STATUS'] ?? ($_GET['code'] ?? 200));
http_response_code($code);
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = file_get_contents('php://input');
echo json_encode([
	'id' => 1,
	'name' => 'Local',
	'method' => $method,
	'body' => $body,
	'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
], JSON_UNESCAPED_UNICODE);
PHP);

		$cmd = sprintf(
			'php -S 127.0.0.1:%d %s',
			$this->port,
			escapeshellarg($this->script)
		);
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['file', sys_get_temp_dir() . '/amoapi-http-out.log', 'a'],
			2 => ['file', sys_get_temp_dir() . '/amoapi-http-err.log', 'a'],
		];
		$this->process = proc_open($cmd, $descriptors, $this->pipes);
		if (!is_resource($this->process)) {
			throw new \RuntimeException('Unable to start LocalHttpServer');
		}

		$deadline = microtime(true) + 3.0;
		while (microtime(true) < $deadline) {
			$errno = 0;
			$errstr = '';
			$socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.2);
			if (is_resource($socket)) {
				fclose($socket);
				return;
			}
			usleep(50000);
		}
		$this->stop();
		throw new \RuntimeException('LocalHttpServer did not become ready on port ' . $this->port);
	}

	public function url(string $path = '/'): string
	{
		if ($path === '' || $path[0] !== '/') {
			$path = '/' . $path;
		}
		return 'http://127.0.0.1:' . $this->port . $path;
	}

	public function stop(): void
	{
		foreach ($this->pipes as $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
			}
		}
		$this->pipes = [];
		if (is_resource($this->process)) {
			proc_terminate($this->process);
			proc_close($this->process);
			$this->process = false;
		}
		if (is_file($this->script)) {
			@unlink($this->script);
		}
	}

	public function __destruct()
	{
		$this->stop();
	}
}
