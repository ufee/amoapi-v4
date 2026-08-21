<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Enums\CustomFields\LegalEntityTypeEnum;
use Ufee\AmoV4\Enums\CustomFields\SmartAddressEnum;
use Ufee\AmoV4\Models\AccountCfield;
use Ufee\AmoV4\Models\EntityCfields\FileField;
use Ufee\AmoV4\Models\Lead;

/**
 * Живой reset() кастомных полей сделки по типам.
 *
 * @group integration
 */
class CustomFieldsResetApiTest extends IntegrationTestCase
{
	/** Типы, которые нельзя заполнить тем же универсальным сценарием. */
	private const SKIP_TYPES = [
		'chained_list',
		'tracking_data',
	];

	/** @var string|null */
	private $uploadedUuid;

	protected function tearDown(): void
	{
		if ($this->uploadedUuid !== null && $this->api !== null) {
			try {
				$this->api->files()->delete($this->uploadedUuid);
			} catch (\Throwable $e) {
				// best-effort
			}
		}
		$this->uploadedUuid = null;
		parent::tearDown();
	}

	public function testResetClearsLeadCustomFieldsByType(): void
	{
		$fields = $this->api->customFields('leads')->get();
		$byType = [];
		foreach ($fields->all() as $field) {
			/** @var AccountCfield $field */
			$type = (string) $field->type;
			if (isset($byType[$type]) || in_array($type, self::SKIP_TYPES, true)) {
				continue;
			}
			if ($this->isSystemish($field)) {
				continue;
			}
			$byType[$type] = $field;
		}

		$this->assertNotEmpty($byType, 'В аккаунте нет кастомных полей сделки для проверки reset()');

		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Reset CF')]);
		$lead->attachTag('amoapi-v4-itest');
		$this->assertTrue($lead->save(), 'Не удалось создать сделку');
		$this->assertNotEmpty($lead->id);
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$failures = [];
		foreach ($byType as $type => $field) {
			try {
				$this->assertResetClearsField($lead, $field, $type);
			} catch (\Throwable $e) {
				$failures[$type] = $e->getMessage();
			}
		}

		$this->assertSame(
			[],
			$failures,
			"reset() не очистил поля: " . json_encode($failures, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
		);
	}

	public function testResetClearsTrackingData(): void
	{
		$field = $this->api->customFields('leads')->get()->find('code', 'UTM_CONTENT')->first();
		if (!$field) {
			$field = $this->api->customFields('leads')->get()->find('type', 'tracking_data')->first();
		}
		if (!$field) {
			$this->markTestSkipped('Нет поля tracking_data');
		}

		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Reset UTM')]);
		$this->assertTrue($lead->save(), 'Не удалось создать сделку');
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$this->assertResetClearsField($lead, $field, 'tracking_data');
	}

	public function testResetClearsChainedList(): void
	{
		$field = $this->api->customFields('leads')->get()->find('type', 'chained_list')->first();
		if (!$field) {
			$this->markTestSkipped('Нет поля chained_list');
		}
		$lists = $field->chained_lists ?? [];
		$firstList = is_array($lists) ? ($lists[0] ?? null) : null;
		$catalogId = is_object($firstList) ? (int) ($firstList->catalog_id ?? 0) : 0;
		if ($catalogId < 1) {
			$this->markTestSkipped('У chained_list нет catalog_id');
		}

		$page = $this->api->catalogElements($catalogId)->maxPageRows(1)->paginate()->fetchPage();
		$element = $page ? $page->first() : null;
		if (!$element) {
			$this->markTestSkipped('В каталоге chained_list нет элементов');
		}

		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Reset Chain')]);
		$this->assertTrue($lead->save(), 'Не удалось создать сделку');
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$query = $this->api->query('PATCH', '/api/v4/leads/' . $lead->id);
		$query->setJsonData([
			'custom_fields_values' => [
				[
					'field_id' => (int) $field->id,
					'values' => [
						[
							'catalog_id' => $catalogId,
							'catalog_element_id' => (int) $element->id,
						],
					],
				],
			],
		]);
		$query->execute();
		$this->assertSame(200, $query->response->getCode(), 'Не удалось записать chained_list');

		$filled = $this->api->leads()->find((int) $lead->id);
		$this->assertNotNull($filled->cf((int) $field->id)->getValue(), 'chained_list не записалось');

		$filled->cf((int) $field->id)->reset();
		$this->assertTrue($filled->save(), 'Не удалось reset() chained_list');

		$cleared = $this->api->leads()->find((int) $lead->id);
		$this->assertNull(
			$cleared->cf((int) $field->id)->getValue(),
			'После reset() chained_list не пустое'
		);
	}

	private function assertResetClearsField(Lead $lead, AccountCfield $field, string $type): void
	{
		$fresh = $this->api->leads()->find((int) $lead->id);
		$this->assertInstanceOf(Lead::class, $fresh);

		if ($type === 'file') {
			if (!$this->seedFileField($fresh, (int) $field->id)) {
				return;
			}
		} else {
			$this->seedField($fresh, $field);
		}
		$this->assertTrue($fresh->save(), "Не удалось записать поле {$type} ({$field->name})");

		$filled = $this->api->leads()->find((int) $lead->id);
		$this->assertFalse(
			$this->isFieldEmpty($filled, $field, $type),
			"Поле {$type} ({$field->name}) не записалось перед reset()"
		);

		$filled->cf((int) $field->id)->reset();
		$this->assertTrue($filled->save(), "Не удалось reset() поля {$type} ({$field->name})");

		$cleared = $this->api->leads()->find((int) $lead->id);
		$this->assertTrue(
			$this->isFieldEmpty($cleared, $field, $type),
			"После reset() поле {$type} ({$field->name}) не пустое, value="
			. json_encode($cleared->cf((int) $field->id)->getRawData(), JSON_UNESCAPED_UNICODE)
		);
	}

	private function seedField(Lead $lead, AccountCfield $field): void
	{
		$cf = $lead->cf((int) $field->id);
		$type = (string) $field->type;
		$stamp = strtotime('2024-06-15 12:00:00');

		switch ($type) {
			case 'text':
			case 'textarea':
			case 'streetaddress':
			case 'tracking_data':
				$cf->setValue('ITEST reset ' . $type);
				return;
			case 'url':
				$cf->setValue('https://example.com/itest-reset');
				return;
			case 'numeric':
			case 'monetary':
			case 'price':
				$cf->setValue(123.45);
				return;
			case 'checkbox':
				$cf->setValue(true);
				return;
			case 'date':
			case 'birthday':
			case 'date_time':
				$cf->setValue($stamp);
				return;
			case 'select':
			case 'radiobutton':
				$values = $field->getValues();
				$this->assertNotEmpty($values, "У поля {$field->name} нет enum");
				$cf->setValue($values[0]);
				return;
			case 'multiselect':
				$values = $field->getValues();
				$this->assertGreaterThanOrEqual(1, count($values), "У поля {$field->name} нет enum");
				$cf->setValues(array_slice($values, 0, min(2, count($values))));
				return;
			case 'smart_address':
				$cf->setValues([
					SmartAddressEnum::CITY => 'Москва',
					SmartAddressEnum::ZIP => '109004',
					SmartAddressEnum::COUNTRY => 'RU',
				]);
				return;
			case 'legal_entity':
				$cf->setName('ООО ITEST Reset')->setEntityType(LegalEntityTypeEnum::LEGAL);
				return;
			case 'multitext':
				$cf->setValue('itest-reset@example.com');
				return;
			default:
				$cf->setValue('ITEST reset');
		}
	}

	/**
	 * @return bool false if Drive unavailable
	 */
	private function seedFileField(Lead $lead, int $fieldId): bool
	{
		try {
			$account = $this->api->account()->get();
			if (empty($account->drive_url)) {
				return false;
			}
			$this->api->setParam('drive_url', $account->drive_url);
			$content = 'amoapi-v4 reset itest ' . uniqid('', false);
			$file = $this->api->files()->upload($content, [
				'file_name' => 'amoapi-v4-reset-itest.txt',
				'content_type' => 'text/plain',
			]);
		} catch (\Throwable $e) {
			return false;
		}
		$this->uploadedUuid = $file->uuid;

		/** @var FileField $cf */
		$cf = $lead->cf($fieldId);
		$cf->setFile($file);
		return true;
	}

	private function isFieldEmpty(Lead $lead, AccountCfield $field, string $type): bool
	{
		$cf = $lead->cf((int) $field->id);
		if ($type === 'file') {
			return !$cf->hasFile();
		}
		if ($type === 'smart_address') {
			return $cf->toArray() === [] && $cf->getValue() === null;
		}
		if ($type === 'legal_entity') {
			return $cf->getName() === null && $cf->toArray() === [];
		}
		if ($type === 'multiselect') {
			return $cf->getValues() === [];
		}
		$value = $cf->getValue();
		return $value === null || $value === '' || $value === [];
	}

	private function isSystemish(AccountCfield $field): bool
	{
		$code = (string) ($field->code ?? '');
		if ($code !== '' && strpos($code, 'UTM_') === 0) {
			return true;
		}
		$name = (string) $field->name;
		return $name === 'Лид создан';
	}
}
