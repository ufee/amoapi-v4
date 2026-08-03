<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\EntityCfields\CheckboxField;
use Ufee\AmoV4\Models\EntityCfields\DateField;
use Ufee\AmoV4\Models\EntityCfields\DateTimeField;
use Ufee\AmoV4\Models\EntityCfields\EntityField;
use Ufee\AmoV4\Models\EntityCfields\FileField;
use Ufee\AmoV4\Models\EntityCfields\JurField;
use Ufee\AmoV4\Models\EntityCfields\MultiSelectField;
use Ufee\AmoV4\Models\EntityCfields\NumericField;
use Ufee\AmoV4\Models\EntityCfields\RadioButtonField;
use Ufee\AmoV4\Models\EntityCfields\SelectField;
use Ufee\AmoV4\Models\EntityCfields\SmartAddressField;
use Ufee\AmoV4\Models\EntityCfields\StreetAddressField;
use Ufee\AmoV4\Models\EntityCfields\TextField;
use Ufee\AmoV4\Models\EntityCfields\UrlField;
use Ufee\AmoV4\Tests\TestCase;

class EntityCfieldsTest extends TestCase
{
	public function testFieldClassMappingByType(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Text', 'TEXT', 'text', 'hello'),
			$this->cf(2, 'Num', 'NUM', 'numeric', '10'),
			$this->cf(3, 'Price', 'PRICE', 'price', '99'),
			$this->cf(4, 'Flag', 'FLAG', 'checkbox', true),
			$this->cf(5, 'Select', 'SEL', 'select', 'A'),
			$this->cf(6, 'Multi', 'MULTI', 'multiselect', 'X'),
			$this->cf(7, 'Date', 'DATE', 'date', 1700000000),
			$this->cf(8, 'Birth', 'BDAY', 'birthday', 946684800),
			$this->cf(9, 'DT', 'DT', 'date_time', 1700000000),
			$this->cf(10, 'Url', 'URL', 'url', 'https://example.com'),
			$this->cf(11, 'Radio', 'RAD', 'radiobutton', 'yes'),
			$this->cf(12, 'Street', 'STR', 'streetaddress', 'Main'),
			$this->cf(13, 'Smart', 'SMART', 'smart_address', 'City'),
			$this->cf(14, 'Jur', 'JUR', 'legal_entity', 'OOO'),
			$this->cf(15, 'File', 'FILE', 'file', 'file-uuid'),
			$this->cf(16, 'Unknown', 'UNK', 'tracking_data', 'track'),
		]);

		$this->assertInstanceOf(TextField::class, $model->cf(1));
		$this->assertInstanceOf(NumericField::class, $model->cf(2));
		$this->assertInstanceOf(NumericField::class, $model->cf(3));
		$this->assertInstanceOf(CheckboxField::class, $model->cf(4));
		$this->assertInstanceOf(SelectField::class, $model->cf(5));
		$this->assertInstanceOf(MultiSelectField::class, $model->cf(6));
		$this->assertInstanceOf(DateField::class, $model->cf(7));
		$this->assertInstanceOf(DateField::class, $model->cf(8));
		$this->assertInstanceOf(DateTimeField::class, $model->cf(9));
		$this->assertInstanceOf(UrlField::class, $model->cf(10));
		$this->assertInstanceOf(RadioButtonField::class, $model->cf(11));
		$this->assertInstanceOf(StreetAddressField::class, $model->cf(12));
		$this->assertInstanceOf(SmartAddressField::class, $model->cf(13));
		$this->assertInstanceOf(JurField::class, $model->cf(14));
		$this->assertInstanceOf(FileField::class, $model->cf(15));
		$this->assertInstanceOf(EntityField::class, $model->cf(16));
		$this->assertNotInstanceOf(TextField::class, $model->cf(16));
	}

	public function testLookupByNameCodeAndType(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(100, 'Email', 'EMAIL', 'multitext', 'a@b.c'),
		]);

		$this->assertSame(100, $model->cf('Email')->field_id);
		$this->assertSame(100, $model->cf()->byCode('EMAIL')->field_id);
		$this->assertSame(100, $model->cf()->byType('multitext')->field_id);
		$this->assertSame(100, $model->cf(100)->field_id);
	}

	public function testSetValueAndResetAffectPayload(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Note', 'NOTE', 'text', 'old'),
		]);

		$model->cf(1)->setValue('new');
		$payload = $model->getChangedRawData();
		$this->assertCount(1, $payload->custom_fields_values);
		$this->assertSame('new', $payload->custom_fields_values[0]->values[0]->value);

		$model->cf(1)->reset();
		$payload = $model->getChangedRawData();
		$this->assertSame([], $payload->custom_fields_values[0]->values);
	}

	public function testSelectAndMultiSelectEnums(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Status', 'ST', 'select'),
			$this->cf(2, 'Tags', 'TG', 'multiselect'),
		]);

		$model->cf(1)->setEnum(11);
		$this->assertSame(11, $model->cf(1)->getEnum());
		$this->assertNull($model->cf(1)->getRawData()->values[0]->value ?? null);

		$model->cf(2)->setEnums([21, 22]);
		$this->assertSame([21, 22], $model->cf(2)->getEnums());

		$model->cf(1)->reset();
		$this->assertNull($model->cf(1)->getRawData()->values);
	}

	public function testDateAndDateTimeFormatting(): void
	{
		$api = $this->makeApiClient();
		$api->setParam('timezone', 'UTC');
		$model = $api->contacts()->create([
			'name' => 'D',
			'custom_fields_values' => [
				$this->cf(1, 'Date', 'D', 'date', 1704067200),
				$this->cf(2, 'DT', 'DT', 'date_time', 1704067200),
			],
		]);

		$this->assertSame('2024-01-01', $model->cf(1)->format('Y-m-d'));
		$this->assertSame('2024-01-01 00:00:00', $model->cf(2)->format());
		$this->assertInstanceOf(\DateTime::class, $model->cf(1)->getDateTime());

		$model->cf(1)->reset();
		$this->assertNull($model->cf(1)->getDateTime());
		$this->assertNull($model->cf(1)->format());
	}

	public function testFileFieldResetKeepsSingleNullValue(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'File', 'F', 'file', 'uuid'),
		]);

		$model->cf(1)->reset();
		$raw = $model->cf(1)->getRawData();
		$this->assertCount(1, $raw->values);
		$this->assertNull($raw->values[0]->value);
	}

	public function testSetValuesAndGetValues(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Multi', 'M', 'multiselect'),
		]);

		$model->cf(1)->setValues(['a', 'b']);
		$this->assertSame(['a', 'b'], $model->cf(1)->getValues());
		$this->assertSame('a', $model->cf(1)->getValue());
	}

	/**
	 * @param list<object> $fields
	 */
	private function makeContactWithFields(array $fields)
	{
		return $this->service('contacts')->create([
			'name' => 'CF',
			'custom_fields_values' => $fields,
		]);
	}

	/**
	 * @param mixed $value
	 */
	private function cf(int $id, string $name, string $code, string $type, $value = null): object
	{
		$field = (object) [
			'field_id' => $id,
			'field_name' => $name,
			'field_code' => $code,
			'field_type' => $type,
			'values' => [],
		];
		if ($value !== null) {
			$field->values = [(object) ['value' => $value]];
		}
		return $field;
	}
}
