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
}
