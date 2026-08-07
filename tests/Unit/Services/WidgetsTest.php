<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Widget;
use Ufee\AmoV4\Services\Widgets;
use Ufee\AmoV4\Tests\TestCase;

class WidgetsTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('widgets');
		$this->assertInstanceOf(Widgets::class, $service);
		$this->assertSame('/api/v4/widgets', $service->api_path);
		$this->assertSame('widgets', $service->entity_key);
		$this->assertSame(
			[Widgets::BOT_SALESBOT, Widgets::BOT_MARKETINGBOT],
			Widgets::botTypeValues()
		);
		$this->assertSame(
			[Widgets::HANDLER_SHOW, Widgets::HANDLER_GOTO],
			Widgets::handlerValues()
		);
	}

	public function testCreateModel(): void
	{
		$widget = $this->service('widgets')->create([
			'id' => 1,
			'code' => 'amo_asterisk',
			'name' => 'Asterisk',
		]);
		$this->assertInstanceOf(Widget::class, $widget);
		$this->assertSame('amo_asterisk', $widget->code);
	}

	public function testModelInstallDelegatesToService(): void
	{
		$service = $this->getMockBuilder(Widgets::class)
			->disableOriginalConstructor()
			->onlyMethods(['install'])
			->getMock();

		$installed = new Widget(['code' => 'amo_asterisk', 'id' => 2], $service);
		$service->expects($this->once())
			->method('install')
			->with('amo_asterisk', ['login' => 'u'])
			->willReturn($installed);

		$widget = new Widget(['code' => 'amo_asterisk'], $service);
		$result = $widget->install(['login' => 'u']);
		$this->assertSame($installed, $result);
	}

	public function testModelUninstallDelegatesToService(): void
	{
		$service = $this->getMockBuilder(Widgets::class)
			->disableOriginalConstructor()
			->onlyMethods(['uninstall'])
			->getMock();

		$service->expects($this->once())
			->method('uninstall')
			->with('amo_asterisk')
			->willReturn(true);

		$widget = new Widget(['code' => 'amo_asterisk'], $service);
		$this->assertTrue($widget->uninstall());
	}

	public function testContinueBotRejectsNonPositiveBotId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Bot ID must be positive integer');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 0, 'cont-1');
	}

	public function testContinueBotRejectsEmptyContinueId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Continue ID must be non-empty');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 1, '');
	}

	public function testContinueBotRejectsInvalidBotType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Bot type must be one of');
		$this->service('widgets')->continueBot('bots', 1, 'c1');
	}

	public function testContinueBotRejectsTooManyHandlers(): void
	{
		$handlers = [];
		for ($i = 0; $i < 11; $i++) {
			$handlers[] = [
				'handler' => Widgets::HANDLER_SHOW,
				'params' => ['type' => 'text', 'value' => 'x'],
			];
		}
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('maximum 10 handlers');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 1, 'c1', [], $handlers);
	}

	public function testContinueBotRejectsInvalidHandlerName(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('handler must be one of: show, goto');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 1, 'c1', [], [[
			'handler' => 'unknown',
			'params' => [],
		]]);
	}

	public function testContinueBotRejectsShowTextLongerThan80(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('must not exceed 80 characters');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 1, 'c1', [], [[
			'handler' => Widgets::HANDLER_SHOW,
			'params' => [
				'type' => 'text',
				'value' => str_repeat('a', 81),
			],
		]]);
	}

	public function testContinueBotRejectsTooManyButtons(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('maximum 25 buttons');
		$this->service('widgets')->continueBot(Widgets::BOT_SALESBOT, 1, 'c1', [], [[
			'handler' => Widgets::HANDLER_SHOW,
			'params' => [
				'type' => 'buttons',
				'value' => 'Pick',
				'buttons' => range(1, 26),
			],
		]]);
	}

	public function testParseContinueUrlFromAbsoluteUrl(): void
	{
		$parsed = Widgets::parseContinueUrl(
			'https://cmdf5.amocrm.ru/api/v4/marketingbot/321/continue/123'
		);
		$this->assertSame([Widgets::BOT_MARKETINGBOT, 321, 123], $parsed);
	}

	public function testParseContinueUrlFromRelativePath(): void
	{
		$parsed = Widgets::parseContinueUrl('/api/v4/salesbot/12/continue/99');
		$this->assertSame([Widgets::BOT_SALESBOT, 12, 99], $parsed);
	}

	public function testParseContinueUrlRejectsInvalid(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid continue return_url format');
		Widgets::parseContinueUrl('https://example.amocrm.ru/api/v4/widgets/amo_x');
	}

	public function testContinueFromUrlDelegatesToContinueBot(): void
	{
		$service = $this->getMockBuilder(Widgets::class)
			->disableOriginalConstructor()
			->onlyMethods(['continueBot'])
			->getMock();

		$service->expects($this->once())
			->method('continueBot')
			->with(Widgets::BOT_MARKETINGBOT, 321, 123, ['status' => 'ok'], [])
			->willReturn(true);

		$this->assertTrue($service->continueFromUrl(
			'https://cmdf5.amocrm.ru/api/v4/marketingbot/321/continue/123',
			['status' => 'ok']
		));
	}
}
