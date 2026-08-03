<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Bot;
use Ufee\AmoV4\Services\Bots;

/**
 * @group integration
 */
class BotsApiTest extends IntegrationTestCase
{
	public function testListBots(): void
	{
		$service = $this->api->bots();
		$this->assertInstanceOf(Bots::class, $service);
		$this->assertSame('items', $service->entity_key);

		$bots = $service->maxPageRows(10)->paginate()->fetchPage();
		$this->assertIsObject($bots);
	}

	public function testFindBotById(): void
	{
		$botId = (int) (getenv('AMO_BOT_ID') ?: 0);
		if ($botId <= 0) {
			$bots = $this->api->bots()->maxPageRows(1)->paginate()->fetchPage();
			$first = $bots->first();
			if (!$first) {
				$this->markTestSkipped('В аккаунте нет salesbot, задайте AMO_BOT_ID');
			}
			$botId = (int) $first->id;
		}

		$bot = $this->api->bots()->find($botId);
		$this->assertInstanceOf(Bot::class, $bot);
		$this->assertSame($botId, (int) $bot->id);
	}

	public function testRunAndStopBotOnLead(): void
	{
		$botId = (int) (getenv('AMO_BOT_ID') ?: 0);
		if ($botId <= 0) {
			$bots = $this->api->bots()->maxPageRows(1)->paginate()->fetchPage();
			$first = $bots->first();
			if (!$first) {
				$this->markTestSkipped('В аккаунте нет salesbot для run/stop, задайте AMO_BOT_ID');
			}
			$botId = (int) $first->id;
		}

		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Bot lead')]);
		$this->assertTrue($lead->save());
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$started = $this->api->bots()->run($botId, (int) $lead->id, 'leads');
		$this->assertTrue($started, 'bots()->run() должен вернуть true (HTTP 202)');

		$stopped = $this->api->bots()->stop($botId, (int) $lead->id, 'leads');
		$this->assertTrue($stopped, 'bots()->stop() должен вернуть true (HTTP 202)');
	}
}
