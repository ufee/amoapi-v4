<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Api\Oauth\FileStorage;
use Ufee\AmoV4\Exceptions\OauthException;
use Ufee\AmoV4\Tests\TestCase;

class OauthRefreshTest extends TestCase
{
	/** @var string|null */
	private $tempDir;

	protected function tearDown(): void
	{
		if ($this->tempDir && is_dir($this->tempDir)) {
			foreach (glob($this->tempDir . '/*/*') ?: [] as $file) {
				@unlink($file);
			}
			foreach (glob($this->tempDir . '/*') ?: [] as $dir) {
				@rmdir($dir);
			}
			@rmdir($this->tempDir);
		}
		parent::tearDown();
	}

	public function testRefreshTokenEmptyThrows(): void
	{
		$api = $this->makeApiClient();
		$api->oauth->setLongToken('access-only');

		$this->expectException(OauthException::class);
		$this->expectExceptionMessage('Empty oauth refresh_token');
		// явная пустая строка: get('refresh_token') у LongTokenStorage отдаёт весь массив
		$api->oauth->refreshToken('');
	}

	public function testRefreshTokenEmptyWithErrorCallbackReturnsFalse(): void
	{
		$api = $this->makeStubApiClient();
		$seen = [];
		$api->callbacks->on('oauth.token.refresh.error', function ($e) use (&$seen) {
			$seen[] = $e->getMessage();
		});
		$api->pushResponse(400, ['hint' => 'no refresh']);

		$result = $api->oauth->refreshToken('');
		$this->assertFalse($result);
		$this->assertNotEmpty($seen);
		$this->assertStringContainsString('Empty oauth refresh_token', $seen[0]);
	}

	public function testRefreshTokenSuccessViaStub(): void
	{
		$api = $this->makeStubApiClient(['domain' => 'oauth-refresh']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-refresh-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-refresh', 0777, true);
		$api->oauth->setStorage(new FileStorage($api, ['path' => $this->tempDir]));
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'old-access',
			'refresh_token' => 'refresh-1',
			'created_at' => time(),
		]);

		$api->pushResponse(200, [
			'token_type' => 'Bearer',
			'expires_in' => 86400,
			'access_token' => 'new-access',
			'refresh_token' => 'refresh-2',
		]);

		$refreshed = false;
		$api->callbacks->on('oauth.token.refresh', function () use (&$refreshed) {
			$refreshed = true;
		});

		$result = $api->oauth->refreshToken();

		$this->assertTrue($refreshed);
		$this->assertSame('new-access', $result['access_token']);
		$this->assertSame('refresh-2', $result['refresh_token']);
		$this->assertSame('new-access', $api->oauth->get('access_token'));
	}

	public function testRefreshTokenHintError(): void
	{
		$api = $this->makeStubApiClient();
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'access_token' => 'a',
			'refresh_token' => 'r',
			'created_at' => time(),
		]);
		$api->pushResponse(400, ['hint' => 'Invalid refresh token']);

		$this->expectException(OauthException::class);
		$this->expectExceptionMessage('Invalid refresh token');
		$api->oauth->refreshToken();
	}

	public function testExecuteRefreshesExpiredToken(): void
	{
		$api = $this->makeStubApiClient(['domain' => 'oauth-expire']);
		$this->tempDir = sys_get_temp_dir() . '/amoapi-oauth-expire-' . uniqid('', true);
		mkdir($this->tempDir . '/oauth-expire', 0777, true);
		$api->oauth->setStorage(new FileStorage($api, ['path' => $this->tempDir]));
		$api->setParam('token_refresh_time', 900);
		$api->oauth->set([
			'token_type' => 'Bearer',
			'expires_in' => 100,
			'access_token' => 'almost-dead',
			'refresh_token' => 'refresh-x',
			'created_at' => time() - 50, // осталось ~50 сек < 900
		]);

		// 1) refreshToken post, 2) find execute
		$api->pushResponse(200, [
			'token_type' => 'Bearer',
			'expires_in' => 86400,
			'access_token' => 'fresh',
			'refresh_token' => 'refresh-y',
		]);
		$api->pushResponse(200, ['id' => 5, 'name' => 'C']);

		$model = $api->contacts()->find(5);
		$this->assertSame(5, $model->id);
		$this->assertSame('fresh', $api->oauth->get('access_token'));
	}
}
