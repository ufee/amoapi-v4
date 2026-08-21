<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\EntityCfields\CheckboxField;
use Ufee\AmoV4\Models\EntityCfields\DateField;
use Ufee\AmoV4\Models\EntityCfields\DateTimeField;
use Ufee\AmoV4\Models\EntityCfields\EntityField;
use Ufee\AmoV4\Models\EntityCfields\FileField;
use Ufee\AmoV4\Models\File;
use Ufee\AmoV4\Models\EntityCfields\LegalEntityField;
use Ufee\AmoV4\Models\EntityCfields\MultiSelectField;
use Ufee\AmoV4\Models\EntityCfields\MultiTextField;
use Ufee\AmoV4\Enums\CustomFields\EmailEnum;
use Ufee\AmoV4\Enums\CustomFields\LegalEntityTypeEnum;
use Ufee\AmoV4\Enums\CustomFields\PhoneEnum;
use Ufee\AmoV4\Enums\CustomFields\SmartAddressEnum;
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
			$this->cf(14, 'Legal', 'LEGAL', 'legal_entity', 'OOO'),
			$this->cf(15, 'File', 'FILE', 'file', 'file-uuid'),
			$this->cf(16, 'Phone', 'PHONE', 'multitext', '+7900'),
			$this->cf(17, 'Area', 'AREA', 'textarea', "line1\nline2"),
			$this->cf(18, 'Track', 'TRACK', 'tracking_data', 'utm'),
			$this->cf(19, 'Money', 'MONEY', 'monetary', '100.50'),
			$this->cf(20, 'Cat', 'CAT', 'category', 'Root'),
			$this->cf(21, 'Unknown', 'UNK', 'items', 'x'),
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
		$this->assertInstanceOf(LegalEntityField::class, $model->cf(14));
		$this->assertInstanceOf(FileField::class, $model->cf(15));
		$this->assertInstanceOf(MultiTextField::class, $model->cf(16));
		$this->assertInstanceOf(TextField::class, $model->cf(17));
		$this->assertInstanceOf(TextField::class, $model->cf(18));
		$this->assertInstanceOf(NumericField::class, $model->cf(19));
		$this->assertInstanceOf(SelectField::class, $model->cf(20));
		$this->assertInstanceOf(EntityField::class, $model->cf(21));
		$this->assertNotInstanceOf(TextField::class, $model->cf(21));
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
		$this->assertNull($payload->custom_fields_values[0]->values);
	}

	public function testMultiTextResetSendsNullValues(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Phone', PhoneEnum::CODE, 'multitext', '+7900'),
			$this->cf(2, 'Email', EmailEnum::CODE, 'multitext', 'a@b.c'),
		]);

		$model->cf(1)->reset();
		$model->cf(2)->reset();
		$payload = $model->getChangedRawData();

		$this->assertCount(2, $payload->custom_fields_values);
		$this->assertNull($payload->custom_fields_values[0]->values);
		$this->assertNull($payload->custom_fields_values[1]->values);
		$this->assertNull($model->cf(1)->getValue());
		$this->assertNull($model->cf(2)->getValue());
	}

	/**
	 * @dataProvider resetNullPayloadTypesProvider
	 */
	public function testResetSendsNullForCommonFieldTypes(string $type, $value): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, ucfirst($type), strtoupper($type), $type, $value),
		]);

		$model->cf(1)->reset();
		$this->assertNull($model->getChangedRawData()->custom_fields_values[0]->values, $type);
		$this->assertNull($model->cf(1)->getValue(), $type);
	}

	public function resetNullPayloadTypesProvider(): array
	{
		return [
			'numeric' => ['numeric', 42],
			'checkbox' => ['checkbox', true],
			'url' => ['url', 'https://example.com'],
			'textarea' => ['textarea', "a\nb"],
			'date' => ['date', 1704067200],
			'date_time' => ['date_time', 1704067200],
			'birthday' => ['birthday', 1704067200],
			'streetaddress' => ['streetaddress', 'ул. Тестовая, 1'],
			'monetary' => ['monetary', '100.50'],
			'tracking_data' => ['tracking_data', 'utm'],
			'radiobutton' => ['radiobutton', 'A'],
			'legal_entity' => ['legal_entity', (object) ['name' => 'ООО Тест']],
			'smart_address' => ['smart_address', 'Москва'],
		];
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

	public function testFileFieldGettersAndHasFile(): void
	{
		$value = (object) [
			'file_uuid' => 'u-1',
			'version_uuid' => 'v-1',
			'file_name' => 'doc.pdf',
			'file_size' => 2048,
		];
		$model = $this->makeContactWithFields([
			$this->cf(1, 'File', 'F', 'file', $value),
			$this->cf(2, 'Empty', 'E', 'file'),
			$this->cf(3, 'Plain', 'P', 'file', 'plain-uuid'),
		]);

		/** @var FileField $cf */
		$cf = $model->cf(1);
		$this->assertTrue($cf->hasFile());
		$this->assertSame('u-1', $cf->getUuid());
		$this->assertSame('v-1', $cf->getVersionUuid());
		$this->assertSame('doc.pdf', $cf->getFileName());
		$this->assertSame(2048, $cf->getFileSize());

		$this->assertFalse($model->cf(2)->hasFile());
		$this->assertNull($model->cf(2)->getUuid());
		$this->assertNull($model->cf(2)->getFileName());
		$this->assertNull($model->cf(2)->getFileSize());

		$this->assertTrue($model->cf(3)->hasFile());
		$this->assertSame('plain-uuid', $model->cf(3)->getUuid());
	}

	public function testFileFieldSetFileFromModelArrayAndObject(): void
	{
		$api = $this->makeApiClient();
		$file = $api->files()->create([
			'uuid' => 'u-9',
			'version_uuid' => 'v-9',
			'name' => 'from-model.pdf',
			'size' => 100,
		]);
		$model = $api->contacts()->create([
			'name' => 'CF',
			'custom_fields_values' => [
				$this->cf(1, 'File', 'F', 'file'),
			],
		]);

		/** @var FileField $cf */
		$cf = $model->cf(1);
		$cf->setFile($file);
		$this->assertSame('u-9', $cf->getUuid());
		$this->assertSame('v-9', $cf->getVersionUuid());
		$this->assertSame('from-model.pdf', $cf->getFileName());
		$this->assertSame(100, $cf->getFileSize());

		$cf->setFile([
			'uuid' => 'u-arr',
			'name' => 'arr.txt',
			'size' => 7,
		]);
		$this->assertSame('u-arr', $cf->getUuid());
		$this->assertSame('arr.txt', $cf->getFileName());
		$this->assertSame(7, $cf->getFileSize());

		$cf->setFile((object) [
			'file_uuid' => 'u-obj',
			'version_uuid' => 'v-obj',
			'file_name' => 'obj.bin',
			'file_size' => 3,
		]);
		$value = $cf->getValue();
		$this->assertSame('u-obj', $value->file_uuid);
		$this->assertSame('v-obj', $value->version_uuid);
		$this->assertSame('obj.bin', $value->file_name);
		$this->assertSame(3, $value->file_size);

		$payload = $model->getChangedRawData();
		$this->assertSame('u-obj', $payload->custom_fields_values[0]->values[0]->value->file_uuid);
	}

	public function testFileFieldSetFileRequiresUuid(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'File', 'F', 'file'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('requires file_uuid or uuid');
		$model->cf(1)->setFile(['file_name' => 'x.txt']);
	}

	public function testFileFieldGetFileLoadsFromDrive(): void
	{
		$api = $this->makeStubApiClient();
		$api->setParam('drive_url', 'https://drive-b.amocrm.ru');
		$model = $api->contacts()->create([
			'name' => 'CF',
			'custom_fields_values' => [
				$this->cf(1, 'File', 'F', 'file', (object) [
					'file_uuid' => 'u-drive',
					'file_name' => 'a.txt',
					'file_size' => 1,
				]),
			],
		]);

		$api->pushResponse(200, [
			'uuid' => 'u-drive',
			'name' => 'a.txt',
			'size' => 1,
		]);

		$file = $model->cf(1)->getFile();
		$this->assertInstanceOf(File::class, $file);
		$this->assertSame('u-drive', $file->uuid);
		$this->assertNull($model->cf(1)->reset()->getFile());
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

	public function testMultiSelectAddAndRemoveValues(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Multi', 'M', 'multiselect', 'a'),
		]);

		/** @var MultiSelectField $cf */
		$cf = $model->cf(1);
		$cf->addValue('b')->addValues(['c', 'a']);
		$this->assertSame(['a', 'b', 'c'], $cf->getValues());

		$cf->removeValue('b')->removeValues(['missing', 'c']);
		$this->assertSame(['a'], $cf->getValues());
		$cf->removeValue('missing');
		$this->assertSame(['a'], $cf->getValues());
	}

	public function testMultiSelectAddAndRemoveEnums(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Multi', 'M', 'multiselect'),
		]);

		/** @var MultiSelectField $cf */
		$cf = $model->cf(1);
		$cf->setEnums([21, 22]);
		$cf->addEnum(22)->addEnums([23, 21]);
		$this->assertSame([21, 22, 23], $cf->getEnums());

		$cf->removeEnum(22)->removeEnums([99, 21]);
		$this->assertSame([23], $cf->getEnums());
	}

	public function testMultiSelectAddRemoveOnLoadedItems(): void
	{
		$model = $this->makeContactWithFields([
			(object) [
				'field_id' => 1,
				'field_name' => 'Multi',
				'field_code' => 'M',
				'field_type' => 'multiselect',
				'values' => [
					(object) ['value' => 'Онлайн', 'enum_id' => 21],
					(object) ['value' => 'СБП', 'enum_id' => 22],
				],
			],
		]);

		/** @var MultiSelectField $cf */
		$cf = $model->cf(1);
		$cf->addValue('Онлайн')->addEnum(22)->addValue('Карта');
		$this->assertSame(['Онлайн', 'СБП', 'Карта'], $cf->getValues());

		$cf->removeEnum(21);
		$this->assertSame(['СБП', 'Карта'], $cf->getValues());
		$cf->removeValue('СБП');
		$this->assertSame(['Карта'], $cf->getValues());
	}

	public function testMultiSelectRawDataOmitsEnumCode(): void
	{
		$model = $this->makeContactWithFields([
			(object) [
				'field_id' => 1,
				'field_name' => 'Multi',
				'field_code' => 'M',
				'field_type' => 'multiselect',
				'values' => [
					(object) ['value' => 'Онлайн', 'enum_id' => 21, 'enum_code' => 'ONLINE'],
					(object) ['value' => 'СБП', 'enum_id' => 22, 'enum_code' => null],
				],
			],
		]);

		/** @var MultiSelectField $cf */
		$cf = $model->cf(1);
		$cf->addValue('Карта');

		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(3, $values);
		$this->assertSame('Онлайн', $values[0]->value);
		$this->assertSame(21, $values[0]->enum_id);
		$this->assertFalse(property_exists($values[0], 'enum_code'));
		$this->assertSame('СБП', $values[1]->value);
		$this->assertSame(22, $values[1]->enum_id);
		$this->assertFalse(property_exists($values[1], 'enum_code'));
		$this->assertSame('Карта', $values[2]->value);
		$this->assertFalse(property_exists($values[2], 'enum_id'));
		$this->assertFalse(property_exists($values[2], 'enum_code'));
	}

	public function testMultiTextValueWithEnumCodeAndId(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Phone', 'PHONE', 'multitext'),
		]);

		/** @var MultiTextField $phone */
		$phone = $model->cf(1);
		$phone->setValue('+79001234567', PhoneEnum::MOB);

		$payload = $model->getChangedRawData();
		$item = $payload->custom_fields_values[0]->values[0];
		$this->assertSame('+79001234567', $item->value);
		$this->assertSame(PhoneEnum::MOB, $item->enum_code);
		$this->assertFalse(property_exists($item, 'enum_id'));
		$this->assertSame(PhoneEnum::MOB, $phone->getEnumCode());

		$phone->setValue('+74991234567', 48224);
		$item = $model->getChangedRawData()->custom_fields_values[0]->values[0];
		$this->assertSame('+74991234567', $item->value);
		$this->assertSame(48224, $item->enum_id);
		$this->assertFalse(property_exists($item, 'enum_code'));
		$this->assertSame(48224, $phone->getEnum());

		$phone->setValues([
			['value' => '+7912', 'enum_code' => PhoneEnum::MOB],
			(object) ['value' => '+7495', 'enum_id' => 11],
			'plain',
		]);
		$phone->addValue('+7800', PhoneEnum::OTHER);

		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(4, $values);
		$this->assertSame('+7912', $values[0]->value);
		$this->assertSame(PhoneEnum::MOB, $values[0]->enum_code);
		$this->assertSame('+7495', $values[1]->value);
		$this->assertSame(11, $values[1]->enum_id);
		$this->assertSame('plain', $values[2]->value);
		$this->assertSame(PhoneEnum::WORK, $values[2]->enum_code);
		$this->assertSame('+7800', $values[3]->value);
		$this->assertSame(PhoneEnum::OTHER, $values[3]->enum_code);
		$this->assertSame(['+7912', '+7495', 'plain', '+7800'], $phone->getValues());
		$this->assertSame([PhoneEnum::MOB, null, PhoneEnum::WORK, PhoneEnum::OTHER], $phone->getEnumCodes());
		$this->assertSame([null, 11, null, null], $phone->getEnums());
	}

	public function testMultiTextDefaultsWorkForPhoneAndEmail(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Phone', PhoneEnum::CODE, 'multitext'),
			$this->cf(2, 'Email', EmailEnum::CODE, 'multitext'),
			$this->cf(3, 'Other', 'CUSTOM', 'multitext'),
		]);

		$model->cf(1)->setValue('+7900');
		$model->cf(2)->setValue('a@b.c');
		$model->cf(3)->setValue('note');

		$payload = $model->getChangedRawData()->custom_fields_values;
		$this->assertSame(PhoneEnum::WORK, $payload[0]->values[0]->enum_code);
		$this->assertSame(EmailEnum::WORK, $payload[1]->values[0]->enum_code);
		$this->assertFalse(property_exists($payload[2]->values[0], 'enum_code'));
	}

	public function testMultiTextRejectsUnknownEnumCode(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(1, 'Phone', PhoneEnum::CODE, 'multitext'),
			$this->cf(2, 'Email', EmailEnum::CODE, 'multitext'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown phone enum "PRIV"');
		$model->cf(1)->setValue('+7900', EmailEnum::PRIV);
	}

	public function testMultiTextRejectsUnknownEmailEnumCode(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(2, 'Email', EmailEnum::CODE, 'multitext'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown email enum "MOB"');
		$model->cf(2)->setValue('a@b.c', PhoneEnum::MOB);
	}

	public function testSmartAddressHelpersAndUpsert(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(13, 'Address', 'ADDR', 'smart_address'),
		]);

		/** @var SmartAddressField $addr */
		$addr = $model->cf(13);
		$addr->setAddressLine1('Николоямская улица 28/60')
			->setCity('Москва')
			->setState('Москва')
			->setZip('109004')
			->setCountry('RU');

		$this->assertSame('Москва', $addr->getCity());
		$this->assertSame('RU', $addr->getCountry());
		$this->assertSame('109004', $addr->getZip());
		$this->assertSame([
			SmartAddressEnum::ADDRESS_LINE_1 => 'Николоямская улица 28/60',
			SmartAddressEnum::CITY => 'Москва',
			SmartAddressEnum::STATE => 'Москва',
			SmartAddressEnum::ZIP => '109004',
			SmartAddressEnum::COUNTRY => 'RU',
		], $addr->toArray());

		$addr->setCity('СПб');
		$this->assertSame('СПб', $addr->getCity());
		$this->assertCount(5, $model->getChangedRawData()->custom_fields_values[0]->values);
		$this->assertSame('СПб', $addr->getValue(SmartAddressEnum::CITY));
		$this->assertSame('СПб', $addr->getValue(3));
	}

	public function testSmartAddressSetValuesMapAndApiFormat(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(13, 'Address', 'ADDR', 'smart_address'),
		]);

		/** @var SmartAddressField $addr */
		$addr = $model->cf(13);
		$addr->setValues([
			SmartAddressEnum::CITY => 'Москва',
			SmartAddressEnum::COUNTRY => 'RU',
		]);
		$this->assertSame(['city' => 'Москва', 'country' => 'RU'], $addr->toArray());

		$addr->setValues([
			['value' => 'Тверская 1', 'enum_code' => SmartAddressEnum::ADDRESS_LINE_1],
			(object) ['value' => '109004', 'enum_id' => 5],
		]);
		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(2, $values);
		$this->assertSame('Тверская 1', $values[0]->value);
		$this->assertSame(SmartAddressEnum::ADDRESS_LINE_1, $values[0]->enum_code);
		$this->assertSame('109004', $values[1]->value);
		$this->assertSame(5, $values[1]->enum_id);
		$this->assertSame('109004', $addr->getZip());
		$this->assertSame([SmartAddressEnum::ADDRESS_LINE_1, SmartAddressEnum::ZIP], $addr->getEnumCodes());
	}

	public function testSmartAddressRequiresEnum(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(13, 'Address', 'ADDR', 'smart_address'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('requires enum_id or enum_code');
		$model->cf(13)->setValue('Москва');
	}

	public function testSmartAddressRejectsUnknownEnum(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(13, 'Address', 'ADDR', 'smart_address'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown smart_address enum "street"');
		$model->cf(13)->setValue('Тверская', 'street');
	}

	public function testLegalEntitySetValueAndHelpers(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		/** @var LegalEntityField $legal */
		$legal = $model->cf(14);
		$legal->setValue([
			'name' => 'ООО Ромашка',
			'entity_type' => LegalEntityTypeEnum::LEGAL,
			'vat_id' => '7701234567',
			'kpp' => '770101001',
			'address' => 'Москва',
			'external_uid' => 'ext-1',
		]);

		$this->assertSame('ООО Ромашка', $legal->getName());
		$this->assertSame(LegalEntityTypeEnum::LEGAL, $legal->getEntityType());
		$this->assertSame('7701234567', $legal->getVatId());
		$this->assertSame('770101001', $legal->getKpp());
		$this->assertSame('Москва', $legal->getAddress());
		$this->assertSame('ext-1', $legal->getExternalUid());

		$value = $model->getChangedRawData()->custom_fields_values[0]->values[0]->value;
		$this->assertSame('ООО Ромашка', $value->name);
		$this->assertSame(2, $value->entity_type);

		$legal->setName('ООО Ромашка')
			->setDirector('Иванов')
			->setBankCode('044525225');
		$this->assertSame('Иванов', $legal->getDirector());
		$this->assertSame('044525225', $legal->getBankCode());
		$this->assertArrayHasKey('vat_id', $legal->toArray()[0]);
	}

	public function testLegalEntityRequiresName(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('legal_entity requires name');
		$model->cf(14)->setValue(['vat_id' => '7701234567']);
	}

	public function testLegalEntityRejectsUnknownEntityType(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown legal_entity entity_type 9');
		$model->cf(14)->setValue([
			'name' => 'Test',
			'entity_type' => 9,
		]);
	}

	public function testLegalEntityFluentRequiresNameFirst(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('requires name before setting other properties');
		$model->cf(14)->setVatId('7701234567');
	}

	public function testLegalEntitySaveOmitsEmptyKeysFromGetPayload(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity', (object) [
				'name' => 'ООО Старое',
				'entity_type' => 2,
				'vat_id' => '7701234567',
				'tax_registration_reason_code' => null,
				'kpp' => '',
				'address' => '',
				'real_address' => null,
				'bank_code' => '',
				'bank_account_number' => '',
				'director' => '',
				'external_uid' => null,
				'unp' => '',
				'bin' => '',
				'egrpou' => '',
				'mfo' => '',
				'oked' => '',
			]),
		]);

		/** @var LegalEntityField $legal */
		$legal = $model->cf(14);
		$legal->setName('ООО "Какие люди"');

		$value = $model->getChangedRawData()->custom_fields_values[0]->values[0]->value;
		$this->assertSame('ООО "Какие люди"', $value->name);
		$this->assertSame(2, $value->entity_type);
		$this->assertSame('7701234567', $value->vat_id);
		$this->assertFalse(property_exists($value, 'mfo'));
		$this->assertFalse(property_exists($value, 'oked'));
		$this->assertFalse(property_exists($value, 'bank_account_number'));
		$this->assertFalse(property_exists($value, 'director'));
		$this->assertFalse(property_exists($value, 'kpp'));
		$this->assertFalse(property_exists($value, 'unp'));
	}

	public function testLegalEntitySaveKeepsFilledCountryKeys(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		/** @var LegalEntityField $legal */
		$legal = $model->cf(14);
		$legal->setValue([
			'name' => 'ТОВ КиївПром',
			'mfo' => '305299',
			'bank_account_number' => 'UA1234567890',
			'director' => 'Іванов',
		]);

		$value = $model->getChangedRawData()->custom_fields_values[0]->values[0]->value;
		$this->assertSame('305299', $value->mfo);
		$this->assertSame('UA1234567890', $value->bank_account_number);
		$this->assertSame('Іванов', $value->director);
	}

	public function testLegalEntityMultipleValues(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		/** @var LegalEntityField $legal */
		$legal = $model->cf(14);
		$legal->setValues([
			['name' => 'ООО А', 'vat_id' => '111'],
			['name' => 'ООО Б', 'vat_id' => '222'],
		]);

		$this->assertSame('ООО А', $legal->getName());
		$this->assertCount(2, $legal->getValues());
		$this->assertSame('ООО А', $legal->toArray()[0]['name']);
		$this->assertSame('ООО Б', $legal->toArray()[1]['name']);

		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(2, $values);
		$this->assertSame('ООО А', $values[0]->value->name);
		$this->assertSame('ООО Б', $values[1]->value->name);
	}

	public function testLegalEntitySetNameKeepsOtherEntities(): void
	{
		$field = $this->cf(14, 'Requisites', 'LEGAL', 'legal_entity', (object) [
			'name' => 'ООО А',
			'vat_id' => '111',
			'mfo' => '',
		]);
		$field->values[] = (object) [
			'value' => (object) [
				'name' => 'ООО Б',
				'vat_id' => '222',
				'oked' => '',
			],
		];
		$model = $this->makeContactWithFields([$field]);

		/** @var LegalEntityField $legal */
		$legal = $model->cf(14);
		$legal->setName('ООО "Какие люди"');
		$legal->addValue(['name' => 'ООО В', 'vat_id' => '333']);

		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(3, $values);
		$this->assertSame('ООО "Какие люди"', $values[0]->value->name);
		$this->assertSame('111', $values[0]->value->vat_id);
		$this->assertFalse(property_exists($values[0]->value, 'mfo'));
		$this->assertSame('ООО Б', $values[1]->value->name);
		$this->assertFalse(property_exists($values[1]->value, 'oked'));
		$this->assertSame('ООО В', $values[2]->value->name);
	}

	public function testLegalEntitySetValueAcceptsList(): void
	{
		$model = $this->makeContactWithFields([
			$this->cf(14, 'Requisites', 'LEGAL', 'legal_entity'),
		]);

		$model->cf(14)->setValue([
			['name' => 'А'],
			['name' => 'Б'],
		]);

		$values = $model->getChangedRawData()->custom_fields_values[0]->values;
		$this->assertCount(2, $values);
		$this->assertSame('А', $values[0]->value->name);
		$this->assertSame('Б', $values[1]->value->name);
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
