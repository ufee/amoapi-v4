<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Collections\Agents as AgentsCollection;
use Ufee\AmoV4\Models\Agent;
use Ufee\AmoV4\Services\Agents;
use Ufee\AmoV4\Tests\TestCase;

class AgentsTest extends TestCase
{
	private const AGENT_ID = 'b1f2c3d4-0000-4a5b-8c9d-000000000001';
	private const AGENT_ID_2 = 'b1f2c3d4-0000-4a5b-8c9d-000000000002';

	public function testServiceMeta(): void
	{
		$service = $this->service('agents');

		$this->assertInstanceOf(Agents::class, $service);
		$this->assertSame('/api/v4/amma/agents', $service->api_path);
		$this->assertSame('agents', $service->entity_key);
		$this->assertSame(50, $service->getQueryArg('limit'));
		$this->assertSame(
			[Agents::MODEL_SIZE_S, Agents::MODEL_SIZE_M, Agents::MODEL_SIZE_L],
			Agents::modelSizeValues()
		);
		$this->assertSame(
			[Agents::TRANSPORT_STREAMABLE_HTTP, Agents::TRANSPORT_SSE],
			Agents::transportValues()
		);
	}

	public function testCreateModelRequiresAgentFields(): void
	{
		$agent = $this->service('agents')->create($this->createPayload());

		$this->assertInstanceOf(Agent::class, $agent);
		$payload = $agent->getChangedRawData();
		$this->assertSame('Помощник по записям', $payload->name);
		$this->assertSame('https://mcp.partner.com/booking', $payload->mcp['url']);
		$this->assertFalse(property_exists($payload, 'id'));
	}

	public function testCreateWithoutRequiredFieldThrows(): void
	{
		$agent = $this->service('agents')->create(['name' => 'Agent']);

		$this->expectException(\Ufee\AmoV4\Exceptions\AmoException::class);
		$this->expectExceptionMessage('required field value not found: description');
		$agent->getChangedRawData();
	}

	public function testSetMcp(): void
	{
		$agent = $this->service('agents')->create($this->createPayload());
		$agent->setMcp('https://mcp.example.com', Agents::TRANSPORT_SSE, [
			'X-Partner-Key' => 'secret',
		]);

		$this->assertSame('https://mcp.example.com', $agent->mcp['url']);
		$this->assertSame(Agents::TRANSPORT_SSE, $agent->mcp['transport']);
		$this->assertSame(['X-Partner-Key' => 'secret'], $agent->mcp['headers']);
	}

	public function testSetMcpUrlOnly(): void
	{
		$agent = $this->service('agents')->create($this->createPayload());
		$agent->setMcp('https://mcp.example.com/v2');

		$this->assertSame(['url' => 'https://mcp.example.com/v2'], $agent->mcp);
	}

	public function testGetChangedRawDataStripsReadonlyAndHasHeaders(): void
	{
		$agent = $this->service('agents')->create([
			'id' => self::AGENT_ID,
			'name' => 'A',
			'client_uuid' => 'a0c11111-2222-4333-8444-555566667777',
			'created_by' => 1,
			'created_at' => 1,
			'updated_at' => 1,
			'mcp' => (object) [
				'url' => 'https://mcp.partner.com/booking',
				'transport' => 'streamable-http',
				'has_headers' => true,
			],
		]);
		$agent->is_active = false;
		$agent->mcp = (object) [
			'url' => 'https://mcp.partner.com/booking',
			'transport' => 'streamable-http',
			'has_headers' => true,
		];

		$payload = $agent->getChangedRawData(['id']);
		$this->assertFalse(property_exists($payload, 'id'));
		$this->assertFalse(property_exists($payload, 'client_uuid'));
		$this->assertFalse($payload->is_active);
		$this->assertFalse(property_exists($payload->mcp, 'has_headers'));
		$this->assertSame('https://mcp.partner.com/booking', $payload->mcp->url);
	}

	public function testFindRejectsNonUuid(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Agent ID must be a valid UUID');
		$this->service('agents')->find('not-a-uuid');
	}

	public function testFindRejectsEmptyString(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Agent ID must be non-empty UUID string');
		$this->service('agents')->find('');
	}

	public function testFindRejectsIntegerId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Agent ID must be non-empty UUID string');
		$this->service('agents')->find(1);
	}

	public function testGetChangedRawDataSanitizesArrayMcp(): void
	{
		$agent = $this->service('agents')->create(['id' => self::AGENT_ID]);
		$agent->mcp = [
			'url' => 'https://mcp.partner.com/booking',
			'has_headers' => true,
		];

		$payload = $agent->getChangedRawData();
		$this->assertSame('https://mcp.partner.com/booking', $payload->mcp['url']);
		$this->assertArrayNotHasKey('has_headers', $payload->mcp);
	}

	public function testGetChangedRawDataKeepsNonStructuredMcp(): void
	{
		$agent = $this->service('agents')->create(['id' => self::AGENT_ID]);
		$agent->mcp = 'https://mcp.partner.com/booking';

		$payload = $agent->getChangedRawData();
		$this->assertSame('https://mcp.partner.com/booking', $payload->mcp);
	}

	public function testGetChangedRawDataClonesMcpWithoutHasHeaders(): void
	{
		$agent = $this->service('agents')->create(['id' => self::AGENT_ID]);
		$mcp = (object) ['url' => 'https://mcp.partner.com/booking', 'transport' => 'sse'];
		$agent->mcp = $mcp;

		$payload = $agent->getChangedRawData();
		$this->assertSame('sse', $payload->mcp->transport);
		$this->assertNotSame($mcp, $payload->mcp);
	}

	public function testMaxPageRowsAcceptsLimit50(): void
	{
		$service = $this->service('agents')->maxPageRows(50);
		$this->assertSame(50, $service->getQueryArg('limit'));
	}

	public function testMaxPageRowsRejectsOutOfRange(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('from 1 to 50');
		$this->service('agents')->maxPageRows(51);
	}

	public function testMaxPageRowsRejectsZero(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->service('agents')->maxPageRows(0);
	}

	public function testFilterIsNotSupported(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('does not support filter');
		$this->service('agents')->filter(['name' => 'A']);
	}

	public function testSearchIsNotSupported(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('does not support search');
		$this->service('agents')->search('query');
	}

	public function testUpdateRejectsBatch(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('do not support batch update');
		$this->service('agents')->update([
			['id' => self::AGENT_ID, 'name' => 'A'],
		]);
	}

	public function testUpdateRejectsInvalidData(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Agent update data must be object or array');
		$this->service('agents')->update(self::AGENT_ID, 'bad');
	}

	public function testRemoveRejectsInvalidId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->service('agents')->remove([self::AGENT_ID]);
	}

	public function testModelDeleteWithoutIdReturnsFalse(): void
	{
		$agent = $this->service('agents')->create($this->createPayload());
		$this->assertFalse($agent->delete());
	}

	public function testModelDeleteDelegatesToRemove(): void
	{
		$service = $this->getMockBuilder(Agents::class)
			->disableOriginalConstructor()
			->onlyMethods(['remove'])
			->getMock();

		$service->expects($this->once())
			->method('remove')
			->with(self::AGENT_ID)
			->willReturn(true);

		$agent = new Agent(['id' => self::AGENT_ID, 'name' => 'X'], $service);
		$this->assertTrue($agent->delete());
	}

	public function testFindReturnsModel(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse());

		$agent = $api->agents()->find(self::AGENT_ID);

		$this->assertInstanceOf(Agent::class, $agent);
		$this->assertSame(self::AGENT_ID, $agent->id);
		$this->assertSame('GET', $api->lastQuery->method);
		$this->assertStringEndsWith('/api/v4/amma/agents/' . self::AGENT_ID, $api->lastQuery->url);
	}

	public function testFindReturnsNullOn404(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(404, '');

		$this->assertNull($api->agents()->find(self::AGENT_ID));
	}

	public function testFindByIdsLoadsEachAgent(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse());
		$api->pushResponse(200, $this->agentResponse(['id' => self::AGENT_ID_2, 'name' => 'Second']));

		$result = $api->agents()->find([self::AGENT_ID, self::AGENT_ID_2]);

		$this->assertInstanceOf(AgentsCollection::class, $result);
		$this->assertCount(2, $result);
		$this->assertSame(self::AGENT_ID, $result->first()->id);
		$this->assertSame(self::AGENT_ID_2, $result->last()->id);
	}

	public function testFindByIdsSkipsMissing(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(404, '');

		$result = $api->agents()->find([self::AGENT_ID]);
		$this->assertCount(0, $result);
	}

	public function testFindByEmptyIdsReturnsEmptyCollection(): void
	{
		$result = $this->service('agents')->find([]);
		$this->assertInstanceOf(AgentsCollection::class, $result);
		$this->assertCount(0, $result);
	}

	public function testAddOneEntity(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(201, [
			'_embedded' => (object) [
				'agents' => [
					(object) $this->agentResponse(),
				],
			],
		]);

		$agent = $api->agents()->create($this->createPayload());
		$this->assertTrue($agent->save());
		$this->assertSame(self::AGENT_ID, $agent->id);
		$this->assertSame('POST', $api->lastQuery->method);
		$this->assertStringEndsWith('/api/v4/amma/agents', $api->lastQuery->url);
	}

	public function testUpdateOneEntityStripsReadonlyFields(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse(['model_size' => 'L', 'is_active' => false]));

		$result = $api->agents()->update(self::AGENT_ID, (object) [
			'id' => self::AGENT_ID,
			'model_size' => 'L',
			'is_active' => false,
			'client_uuid' => 'should-go',
		]);

		$this->assertSame(self::AGENT_ID, $result->id);
		$this->assertSame('PATCH', $api->lastQuery->method);
		$this->assertStringEndsWith('/api/v4/amma/agents/' . self::AGENT_ID, $api->lastQuery->url);
		$sent = $api->lastQuery->json_data;
		$this->assertArrayNotHasKey('id', $sent);
		$this->assertArrayNotHasKey('client_uuid', $sent);
		$this->assertSame('L', $sent['model_size']);
	}

	public function testUpdateAcceptsArrayPayload(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse(['name' => 'New']));

		$result = $api->agents()->update(self::AGENT_ID, ['name' => 'New']);
		$this->assertSame('New', $result->name);
	}

	public function testUpdateStripsMcpHasHeaders(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse());

		$api->agents()->update(self::AGENT_ID, (object) [
			'mcp' => (object) [
				'url' => 'https://mcp.partner.com/booking',
				'transport' => 'sse',
				'has_headers' => true,
			],
		]);

		$sent = $api->lastQuery->json_data;
		$this->assertSame('https://mcp.partner.com/booking', $sent['mcp']->url);
		$this->assertFalse(property_exists($sent['mcp'], 'has_headers'));
	}

	public function testUpdateStripsArrayMcpHasHeaders(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse());

		$api->agents()->update(self::AGENT_ID, [
			'mcp' => [
				'url' => 'https://mcp.partner.com/booking',
				'has_headers' => true,
			],
		]);

		$sent = $api->lastQuery->json_data;
		$this->assertSame('https://mcp.partner.com/booking', $sent['mcp']['url']);
		$this->assertArrayNotHasKey('has_headers', $sent['mcp']);
	}

	public function testSaveUpdateSendsOnlyChangedFields(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, $this->agentResponse());
		$agent = $api->agents()->find(self::AGENT_ID);

		$api->pushResponse(200, $this->agentResponse(['system_prompt' => 'Updated prompt']));
		$agent->system_prompt = 'Updated prompt';
		$this->assertTrue($agent->save());

		$sent = $api->lastQuery->json_data;
		$this->assertSame('Updated prompt', $sent['system_prompt']);
		$this->assertArrayNotHasKey('id', $sent);
		$this->assertArrayNotHasKey('name', $sent);
	}

	public function testRemoveReturnsTrueOn204(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');

		$this->assertTrue($api->agents()->remove(self::AGENT_ID));
		$this->assertSame('DELETE', $api->lastQuery->method);
		$this->assertStringEndsWith('/api/v4/amma/agents/' . self::AGENT_ID, $api->lastQuery->url);
	}

	public function testRemoveReturnsFalseOnError(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(404, '');

		$this->assertFalse($api->agents()->remove(self::AGENT_ID));
	}

	public function testListViaGet(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_total_items' => 1,
			'_page' => 1,
			'_page_count' => 1,
			'_links' => (object) [],
			'_embedded' => (object) [
				'agents' => [
					(object) $this->agentResponse(),
				],
			],
		]);
		$api->pushResponse(204, '');

		$agents = $api->agents()->get();
		$this->assertCount(1, $agents);
		$this->assertInstanceOf(Agent::class, $agents->first());
	}

	public function testCollectionSaveEmptyReturnsFalse(): void
	{
		$collection = $this->service('agents')->createCollection();
		$this->assertFalse($collection->save());
	}

	public function testCollectionSaveReturnsFalseWhenItemSaveFails(): void
	{
		$service = $this->service('agents');
		$agent = $this->getMockBuilder(Agent::class)
			->setConstructorArgs([['id' => self::AGENT_ID, 'name' => 'X'], $service])
			->onlyMethods(['save'])
			->getMock();
		$agent->method('save')->willReturn(false);

		$collection = new AgentsCollection([$agent]);
		$this->assertFalse($collection->save());
	}

	public function testCollectionSaveCreatesBatch(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(201, [
			'_embedded' => (object) [
				'agents' => [
					(object) array_merge($this->agentResponse(), ['request_id' => '0']),
				],
			],
		]);

		$collection = $api->agents()->createCollection([$this->createPayload()]);
		$this->assertTrue($collection->save());
		$this->assertSame(self::AGENT_ID, $collection->first()->id);
		$this->assertSame('POST', $api->lastQuery->method);
	}

	public function testCollectionSaveUpdatesOneByOne(): void
	{
		$api = $this->makeStubApiClient();
		$existing = $this->agentResponse();
		$collection = $api->agents()->createCollection([$existing]);
		$collection->first()->name = 'Renamed';

		$api->pushResponse(200, $this->agentResponse(['name' => 'Renamed']));
		$this->assertTrue($collection->save());
		$this->assertSame('PATCH', $api->lastQuery->method);
		$this->assertStringContainsString(self::AGENT_ID, $api->lastQuery->url);
	}

	private function createPayload(): array
	{
		return [
			'name' => 'Помощник по записям',
			'description' => 'Проверяет записи клиентов и подсказывает свободные слоты',
			'system_prompt' => 'Ты — ассистент по онлайн-записи.',
			'mcp' => [
				'url' => 'https://mcp.partner.com/booking',
				'transport' => Agents::TRANSPORT_STREAMABLE_HTTP,
			],
		];
	}

	private function agentResponse(array $overrides = []): array
	{
		return array_merge([
			'id' => self::AGENT_ID,
			'name' => 'Помощник по записям',
			'description' => 'Проверяет записи клиентов и подсказывает свободные слоты',
			'system_prompt' => 'Ты — ассистент по онлайн-записи.',
			'ai_instructions' => 'Передавай этому агенту вопросы о записи',
			'avatar' => 'https://drive-b.amocrm.ru/download/avatar.png',
			'model_size' => 'M',
			'mcp' => (object) [
				'url' => 'https://mcp.partner.com/booking',
				'transport' => 'streamable-http',
				'has_headers' => true,
			],
			'is_active' => true,
			'client_uuid' => 'a0c11111-2222-4333-8444-555566667777',
			'created_by' => 123456,
			'created_at' => 1753305600,
			'updated_at' => 1753305600,
		], $overrides);
	}
}
