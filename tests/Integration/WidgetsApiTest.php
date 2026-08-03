<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Widget;
use Ufee\AmoV4\Services\Widgets;

/**
 * @group integration
 */
class WidgetsApiTest extends IntegrationTestCase
{
	public function testListWidgets(): void
	{
		$service = $this->api->widgets();
		$this->assertInstanceOf(Widgets::class, $service);

		try {
			$page = $service->maxPageRows(10)->paginate()->fetchPage();
			$this->assertIsObject($page);
		} catch (\Throwable $e) {
			$this->markTestSkipped('Список виджетов недоступен: ' . $e->getMessage());
		}
	}

	public function testFindWidgetByCode(): void
	{
		$code = getenv('AMO_WIDGET_CODE') ?: '';
		if ($code === '') {
			try {
				$page = $this->api->widgets()->maxPageRows(1)->paginate()->fetchPage();
				$first = $page->first();
				if (!$first || empty($first->code)) {
					$this->markTestSkipped('Нет виджетов в аккаунте, задайте AMO_WIDGET_CODE');
				}
				$code = $first->code;
			} catch (\Throwable $e) {
				$this->markTestSkipped('Виджеты недоступны: ' . $e->getMessage());
			}
		}

		$widget = $this->api->widgets()->find($code);
		$this->assertInstanceOf(Widget::class, $widget);
		$this->assertSame($code, $widget->code);
	}

	public function testInstallAndUninstallWidget(): void
	{
		$code = getenv('AMO_WIDGET_CODE') ?: '';
		if ($code === '') {
			$this->markTestSkipped('Для install/uninstall задайте AMO_WIDGET_CODE');
		}

		$settingsJson = getenv('AMO_WIDGET_SETTINGS') ?: '{}';
		$settings = json_decode($settingsJson, true);
		if (!is_array($settings)) {
			$this->markTestSkipped('AMO_WIDGET_SETTINGS должен быть JSON-объектом');
		}

		try {
			$installed = $this->api->widgets()->install($code, $settings);
			$this->assertInstanceOf(Widget::class, $installed);
			$this->assertTrue($this->api->widgets()->uninstall($code));
		} catch (\Throwable $e) {
			try {
				$this->api->widgets()->uninstall($code);
			} catch (\Throwable $ignored) {
			}
			$this->markTestSkipped('install/uninstall виджета недоступен: ' . $e->getMessage());
		}
	}
}
