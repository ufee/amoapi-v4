<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Bot;
use Ufee\AmoV4\Services\Bots;
use Ufee\AmoV4\Tests\TestCase;

class BotsTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('bots');

		$this->assertInstanceOf(Bots::class, $service);
		$this->assertSame('/api/v4/bots', $service->api_path);
		$this->assertSame('items', $service->entity_key);
		$this->assertSame([Bots::FAVORITE], Bots::withValues());
		$this->assertSame(
			[Bots::TYPE_REGULAR, Bots::TYPE_GREETING, Bots::TYPE_MARKETING, Bots::TYPE_NPS],
			Bots::typeFunctionalityValues()
		);
	}

	public function testCreateReturnsBotModel(): void
	{
		$bot = $this->service('bots')->create(['id' => 10, 'name' => 'Salesbot']);

		$this->assertInstanceOf(Bot::class, $bot);
		$this->assertSame(10, $bot->id);
		$this->assertSame('Salesbot', $bot->name);
	}

	public function testFindRejectsNonPositiveId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Bot ID must be positive integer');
		$this->service('bots')->find(0);
	}

	public function testFindRejectsStringId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Bot ID must be positive integer');
		$this->service('bots')->find('1');
	}

	public function testRunRequiresEntityIdForShortcut(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Entity ID must be positive integer');
		$this->service('bots')->run(1);
	}

	public function testRunRejectsEmptyPayload(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('can not be empty');
		$this->service('bots')->run([]);
	}

	public function testRunRejectsMoreThan100Tasks(): void
	{
		$tasks = [];
		for ($i = 1; $i <= 101; $i++) {
			$tasks[] = [
				'bot_id' => 1,
				'entity_id' => $i,
				'entity_type' => 'leads',
			];
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('maximum 100 tasks');
		$this->service('bots')->run($tasks);
	}

	public function testRunRejectsInvalidEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('entity_type must be one of');
		$this->service('bots')->run([[
			'bot_id' => 1,
			'entity_id' => 2,
			'entity_type' => 'companies',
		]]);
	}

	public function testRunRejectsInvalidTaskShape(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Each bot task must be an array');
		$this->service('bots')->run(['not-array']);
	}

	public function testStopRejectsUnsupportedEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('must be one of: leads');
		$this->service('bots')->stop(1, 2, 'contacts');
	}

	public function testStopRejectsCustomersEntityType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('must be one of: leads');
		$this->service('bots')->stop(1, 2, 'customers');
	}

	public function testStopRejectsNonPositiveIds(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Bot ID must be positive integer');
		$this->service('bots')->stop(0, 2, 'leads');
	}
}
