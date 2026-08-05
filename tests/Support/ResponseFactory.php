<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Api\Response;
use Ufee\AmoV4\ApiClient;

final class ResponseFactory
{
	public static function make(ApiClient $api, $body, int $httpCode = 200): Response
	{
		$query = $api->query('GET', '/api/v4/test');
		$query->prepare();

		$data = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
		$response = new Response($data, $query);
		self::forceHttpCode($response, $httpCode);

		return $response;
	}

	public static function forceHttpCode(Response $response, int $httpCode): void
	{
		$ref = new \ReflectionClass($response);

		$code = $ref->getProperty('code');
		$code->setAccessible(true);
		$code->setValue($response, $httpCode);

		$info = $ref->getProperty('info');
		$info->setAccessible(true);
		$current = $info->getValue($response);
		if (!is_object($current)) {
			$current = (object) [];
		}
		$current->http_code = $httpCode;
		$info->setValue($response, $current);
	}

	public static function attachResponse(Query $query, Response $response): void
	{
		$ref = new \ReflectionClass(Query::class);
		$attr = $ref->getProperty('attributes');
		$attr->setAccessible(true);
		$attributes = $attr->getValue($query);
		$attributes['response'] = $response;
		if (isset($attributes['curl']) && is_resource($attributes['curl'])) {
			curl_close($attributes['curl']);
		}
		$attributes['curl'] = null;
		$attr->setValue($query, $attributes);
	}
}
