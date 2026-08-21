<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Api;

use Ufee\AmoV4\Exceptions\AmoException;
use Ufee\AmoV4\Exceptions\UnauthorizedException;
use Ufee\AmoV4\Exceptions\ValidatorException;
use Ufee\AmoV4\Tests\Support\ResponseFactory;
use Ufee\AmoV4\Tests\TestCase;

class ResponseTest extends TestCase
{
	public function testValidatedReturnsDecodedJson(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['id' => 5, 'name' => 'X'], 200);

		$data = $response->validated();
		$this->assertSame(5, $data->id);
		$this->assertSame('X', $data->name);
		$this->assertSame(200, $response->getCode());
	}

	public function testValidatedRejectsNonJson(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, 'not-json', 200);

		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('non JSON');
		$response->validated();
	}

	public function testValidatedThrowsUnauthorized(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['detail' => 'Invalid token'], 401);

		$this->expectException(UnauthorizedException::class);
		$this->expectExceptionMessage('Invalid token');
		$response->validated();
	}

	public function testValidatedThrowsValidatorExceptionOn400(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, [
			'detail' => 'Request validation failed',
			'validation-errors' => [
				(object) ['errors' => [['code' => 'FieldMissing']]],
			],
		], 400);

		$this->expectException(ValidatorException::class);
		$this->expectExceptionMessage('Request validation failed');
		$response->validated();
	}

	public function testValidatedEntities(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, [
			'_embedded' => [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A'],
					(object) ['id' => 2, 'name' => 'B'],
				],
			],
		], 200);

		$rows = $response->validatedEntities('contacts');
		$this->assertCount(2, $rows);
		$this->assertSame(1, $rows[0]->id);
	}

	public function testValidatedEntitiesWithoutEmbedded(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['id' => 1], 200);

		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('embedded not found');
		$response->validatedEntities('contacts');
	}

	public function testValidatedCreatedEntitiesWithValidationErrors(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, [
			'detail' => 'Create failed',
			'validation-errors' => [
				(object) ['errors' => [['code' => 'Bad']]],
			],
		], 200);

		$this->expectException(ValidatorException::class);
		$this->expectExceptionMessage('Create failed');
		$response->validatedCreatedEntities('contacts');
	}

	public function testValidatedUpdatedEntity(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['id' => 42, 'name' => 'Up'], 200);

		$row = $response->validatedUpdatedEntity(42);
		$this->assertSame(42, $row->id);
	}

	public function testValidatedUpdatedEntityRejectsIdMismatch(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['id' => 1], 200);

		$this->expectException(AmoException::class);
		$this->expectExceptionMessage('id not found or not match');
		$response->validatedUpdatedEntity(42);
	}

	public function testValidatedUpdatedEntityAcceptsUuid(): void
	{
		$api = $this->makeApiClient();
		$uuid = 'b1f2c3d4-0000-4a5b-8c9d-000000000001';
		$response = ResponseFactory::make($api, ['id' => $uuid, 'name' => 'Agent'], 200);

		$row = $response->validatedUpdatedEntity($uuid);
		$this->assertSame($uuid, $row->id);
	}

	public function testParseJsonAsArray(): void
	{
		$api = $this->makeApiClient();
		$response = ResponseFactory::make($api, ['ok' => true], 200);
		$this->assertSame(['ok' => true], $response->parseJson(true));
		$this->assertSame('{"ok":true}', $response->getData());
	}
}
