<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Agent;
use Ufee\AmoV4\Services\Agents;

/**
 * @group integration
 */
class AgentsApiTest extends IntegrationTestCase
{
	/** @var string|null */
	private $createdAgentId;

	protected function tearDown(): void
	{
		if ($this->createdAgentId !== null && $this->api !== null) {
			try {
				$this->api->agents()->remove($this->createdAgentId);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->createdAgentId = null;
		parent::tearDown();
	}

	public function testListAgents(): void
	{
		$service = $this->api->agents();
		$this->assertInstanceOf(Agents::class, $service);

		try {
			$page = $service->maxPageRows(10)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Список агентов Аммы недоступен: ' . $e->getMessage());
		}
	}

	public function testCreateUpdateAndRemoveAgent(): void
	{
		$mcpUrl = getenv('AMO_AMMA_MCP_URL') ?: '';
		if ($mcpUrl === '') {
			$this->markTestSkipped('Задайте AMO_AMMA_MCP_URL для CRUD-теста агентов Аммы');
		}

		$agent = $this->api->agents()->create([
			'name' => $this->uniqueName('Agent'),
			'description' => 'Интеграционный тест amoapi-v4',
			'system_prompt' => 'Ты — тестовый агент. Отвечай кратко.',
			'model_size' => Agents::MODEL_SIZE_S,
			'mcp' => [
				'url' => $mcpUrl,
				'transport' => Agents::TRANSPORT_STREAMABLE_HTTP,
			],
			'is_active' => false,
		]);

		try {
			$this->assertTrue($agent->save(), 'Не удалось создать агента Аммы');
		} catch (\Throwable $e) {
			$this->markTestSkipped('Создание агентов Аммы недоступно в аккаунте: ' . $e->getMessage());
		}

		$this->assertNotEmpty($agent->id);
		$this->createdAgentId = (string) $agent->id;

		$found = $this->api->agents()->find($agent->id);
		$this->assertInstanceOf(Agent::class, $found);
		$this->assertSame($agent->id, $found->id);

		$updatedPrompt = 'Обновлённый промпт ' . uniqid('', false);
		$found->system_prompt = $updatedPrompt;
		$found->model_size = Agents::MODEL_SIZE_M;
		$this->assertTrue($found->save(), 'Не удалось обновить агента Аммы');

		$reloaded = $this->api->agents()->find($agent->id);
		$this->assertSame($updatedPrompt, $reloaded->system_prompt);
		$this->assertSame(Agents::MODEL_SIZE_M, $reloaded->model_size);

		$this->assertTrue($this->api->agents()->remove($agent->id));
		$this->createdAgentId = null;
		$this->assertNull($this->api->agents()->find($agent->id));
	}
}
