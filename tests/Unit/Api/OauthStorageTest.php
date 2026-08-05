<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Oauth\FileStorage;
use Ufee\AmoV4\Api\Oauth\LongTokenStorage;
use Ufee\AmoV4\Tests\TestCase;

class OauthStorageTest extends TestCase
{
	/** @var string|null */
	private $tempDir;

	protected function tearDown(): void
	{
		if ($this->tempDir && is_dir($this->tempDir)) {
			$files = glob($this->tempDir . '/*/*') ?: [];
			foreach ($files as $file) {
				@unlink($file);
			}
			$dirs = glob($this->tempDir . '/*') ?: [];
			foreach ($dirs as $dir) {
				@rmdir($dir);
			}
			@rmdir($this->tempDir);
		}
		parent::tearDown();
	}

	public function testLongTokenStorageInitialize(): void
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('long-secret-token');

		$data = $api->oauth->get();
		$this->assertSame('Bearer', $data['token_type']);
		$this->assertSame('long-secret-token', $data['access_token']);
		$this->assertGreaterThan(time(), $data['created_at'] + $data['expires_in']);
	}

	public function testLongTokenStorageRequiresToken(): void
	{
		$api = $this->makeApiClient();
		$this->expectException(\InvalidArgumentException::class);
		new LongTokenStorage($api, []);
	}

	public function testFileStorageSetAndGetRaw(): void
	{
		$api = $this->makeApiClient(['domain' => 'filetest']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-' . uniqid('', true);
		mkdir($this->tempDir . '/filetest', 0777, true);

		$storage = new FileStorage($api, ['path' => $this->tempDir]);
		$oauth = [
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'access',
			'refresh_token' => 'refresh',
			'created_at' => time(),
		];
		$this->assertTrue($storage->set($oauth));

		$path = $this->tempDir . '/filetest/' . $api->client_id . '.json';
		$this->assertFileExists($path);

		$raw = $storage->getRaw();
		$this->assertSame('access', $raw['access_token']);
		$this->assertSame('refresh', $raw['refresh_token']);
	}

	public function testOauthGetUrl(): void
	{
		$api = $this->makeApiClient([
			'domain' => 'acme',
			'redirect_uri' => 'https://app.test/callback',
		]);
		$url = $api->oauth->getUrl(['state' => 'custom']);

		$this->assertStringContainsString('acme.amocrm.ru/oauth?', $url);
		$this->assertStringContainsString('client_id=' . urlencode($api->client_id), $url);
		$this->assertStringContainsString('state=custom', $url);
		$this->assertStringContainsString('redirect_uri=' . urlencode('https://app.test/callback'), $url);
	}

	public function testOauthSetAndGetField(): void
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('tok');
		$this->assertSame('tok', $api->oauth->get('access_token'));
	}
}
