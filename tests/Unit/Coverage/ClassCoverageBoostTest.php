<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Coverage;

use Ufee\AmoV4\Api\Cache\AbstractStorage;
use Ufee\AmoV4\Api\Oauth\FileStorage as OauthFileStorage;
use Ufee\AmoV4\Collections\Collection;
use Ufee\AmoV4\Collections\Contacts;
use Ufee\AmoV4\Collections\CustomFields;
use Ufee\AmoV4\Collections\Pipelines;
use Ufee\AmoV4\Collections\Users;
use Ufee\AmoV4\Models\Account;
use Ufee\AmoV4\Models\AccountCfield;
use Ufee\AmoV4\Models\Event;
use Ufee\AmoV4\Models\Lead;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Models\Task;
use Ufee\AmoV4\Models\User;
use Ufee\AmoV4\Services\Links as LinksService;
use Ufee\AmoV4\Tests\TestCase;

/**
 * Добивает мелкие/почти покрытые классы до 100% (метрика Classes в PHPUnit).
 */
class ClassCoverageBoostTest extends TestCase
{
	private function seedCache($api): AbstractStorage
	{
		$storage = new AbstractStorage($api, []);
		$storage->initialize();
		$api->cache->setStorage($storage);
		return $storage;
	}

	public function testUnsortedRemainingBranches(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'unsorted' => [(object) ['uid' => 'obj-1', 'category' => 'sip']],
			],
		]);

		$created = $api->unsorted()->addSip((object) [
			'source_name' => 'PBX',
			'source_uid' => 's',
			'pipeline_id' => 1,
			'metadata' => ['phone' => 1],
			'_embedded' => ['leads' => [['name' => 'L']]],
		]);
		$this->assertSame('obj-1', $created->uid);

		try {
			$api->unsorted()->addForms('bad');
			$this->fail('Expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('array or object', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('can not be empty');
		$api->unsorted()->accept('');
	}

	public function testEntityFieldEnumNullFieldPropAndUnknown(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);
		$cf = new AccountCfield([
			'id' => 100,
			'name' => 'City',
			'code' => 'CITY',
			'type' => 'text',
		], $api->customFields('contacts'));
		$storage->set('customFields-contacts', new CustomFields([$cf]), 60);

		$model = $api->contacts()->create([
			'name' => 'C',
			'custom_fields_values' => [
				(object) [
					'field_id' => 100,
					'field_name' => 'City',
					'field_code' => 'CITY',
					'field_type' => 'text',
					'values' => [],
				],
			],
		]);

		$field = $model->cf(100);
		$this->assertNull($field->getEnum());
		$this->assertNull($field->unknown_prop);
		$this->assertSame(100, $field->field->id);

		$model->cf(100)->reset();
		$multi = $api->contacts()->create([
			'name' => 'M',
			'custom_fields_values' => [
				(object) [
					'field_id' => 200,
					'field_name' => 'Tags',
					'field_code' => 'T',
					'field_type' => 'multiselect',
					'values' => [],
				],
			],
		]);
		$this->assertNull($multi->cf(200)->getRawData()->values);
	}

	public function testPipelineDeleteStatus(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');
		$pipeline = $api->pipelines()->create([
			'id' => 7,
			'name' => 'P',
			'_embedded' => ['statuses' => []],
		]);
		$this->assertTrue($pipeline->deleteStatus(99));
		$this->assertStringEndsWith('/statuses/99', $api->lastQuery->url);
	}

	public function testCustomerEmptySegments(): void
	{
		$customer = $this->service('customers')->create(['name' => 'C']);
		$this->assertSame($customer, $customer->setSegments([]));
		$this->assertArrayNotHasKey('segments', (array) $customer->getChangedRawData());
	}

	public function testTaskEmptyResultAndType(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);
		$account = new Account([
			'id' => 1,
			'name' => 'A',
			'_embedded' => [
				'users_groups' => [],
				'task_types' => [(object) ['id' => 2, 'name' => 'Call']],
			],
		], $api->account());
		$storage->set('account', $account, 60);

		$task = $api->tasks()->create([
			'id' => 1,
			'text' => 'T',
			'complete_till' => time(),
			'task_type_id' => 2,
		]);
		$task->setCompleted(true);
		$this->assertSame([], $task->result);
		$this->assertSame('Call', $task->type()->name);
	}

	public function testEventTypeUserGroupResponsibleUser(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);

		$storage->set(
			'eventTypes-ru',
			new Collection([(object) ['key' => 'lead_added', 'name' => 'Lead']]),
			60
		);
		$event = $api->events()->create(['id' => 'e1', 'type' => 'lead_added']);
		$this->assertSame('Lead', $event->type()->name);

		$account = new Account([
			'id' => 1,
			'name' => 'A',
			'_embedded' => [
				'users_groups' => [(object) ['id' => 3, 'name' => 'Sales']],
				'task_types' => [],
			],
		], $api->account());
		$storage->set('account', $account, 60);

		$user = $api->users()->create(['id' => 9, 'name' => 'Ivan', 'group_id' => 3]);
		$this->assertSame('Sales', $user->group()->name);

		$users = new Users([
			new User(['id' => 9, 'name' => 'Ivan', 'group_id' => 3], $api->users()),
		]);
		$storage->set('users', $users, 60);

		$contact = $api->contacts()->create(['id' => 1, 'name' => 'C', 'responsible_user_id' => 9]);
		$this->assertSame(9, $contact->responsibleUser()->id);

		$noResp = $api->contacts()->create(['id' => 2, 'name' => 'N']);
		$this->assertNull($noResp->responsibleUser());
	}

	public function testLinkDelete(): void
	{
		$service = $this->getMockBuilder(LinksService::class)
			->disableOriginalConstructor()
			->onlyMethods(['delete'])
			->getMock();
		$fields = ['to_entity_id' => 5, 'to_entity_type' => 'leads', 'entity_id' => 1];
		$service->expects($this->once())->method('delete')->with($fields)->willReturn(true);

		$link = new Link($fields, $service);
		$this->assertTrue($link->delete());
	}

	public function testServiceNotesAndCustomFieldsTraits(): void
	{
		$contacts = $this->service('contacts');
		$this->assertSame('/api/v4/contacts/notes', $contacts->notes()->api_path);
		$this->assertSame('/api/v4/contacts/custom_fields', $contacts->customFields()->api_path);
	}

	public function testPipelinesDeleteViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(204, '');
		$this->assertTrue($api->pipelines()->delete(15));
		$this->assertStringEndsWith('/pipelines/15', $api->lastQuery->url);

		$api->pushResponse(400, ['detail' => 'no']);
		$this->assertFalse($api->pipelines()->delete(16));
	}

	public function testEventsTypesViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'events_types' => [
					(object) ['key' => 'lead_added', 'name' => 'Lead'],
				],
			],
		]);
		$types = $api->events()->types('en');
		$this->assertCount(1, $types);
		$this->assertSame('lead_added', $types->first()->key);
		$this->assertSame('en', $api->lastQuery->args['language_code']);
	}

	public function testWidgetsInstallUninstallViaStub(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, ['id' => 1, 'code' => 'w1', 'name' => 'W']);
		$widget = $api->widgets()->install('w1', ['login' => 'u']);
		$this->assertSame('w1', $widget->code);

		$api->pushResponse(204, '');
		$this->assertTrue($api->widgets()->uninstall('w1'));
	}

	public function testOauthFileStorageRequiresPath(): void
	{
		$api = $this->makeApiClient();
		$this->expectException(\InvalidArgumentException::class);
		new OauthFileStorage($api, []);
	}

	public function testQueriesViaInterfaces(): void
	{
		$api = $this->makeApiClient();
		$api->queries->viaInterfaces(['127.0.0.1']);
		$this->assertSame('127.0.0.1', $api->queries->getInterface());
		$api->queries->viaInterfaces([]);
		$this->assertNull($api->queries->getInterface());
	}

	public function testAccountCfieldEnumsHelpers(): void
	{
		$field = $this->service('customFields', 'contacts')->create([
			'id' => 1,
			'name' => 'Status',
			'enums' => [
				(object) ['id' => 10, 'value' => 'New'],
				(object) ['id' => 11, 'value' => 'Done'],
			],
		]);
		$this->assertTrue($field->hasEnum(10));
		$this->assertFalse($field->hasEnum(99));
		$this->assertTrue($field->hasValue('New'));
		$this->assertSame([10, 11], $field->getEnumIds());
		$this->assertSame(['New', 'Done'], $field->getValues());
	}

	public function testEntitiesCollectionSave(): void
	{
		$api = $this->makeStubApiClient();
		$api->pushResponse(200, [
			'_embedded' => (object) [
				'contacts' => [
					(object) ['id' => 1, 'name' => 'A', 'request_id' => '0'],
					(object) ['id' => 2, 'name' => 'B', 'request_id' => '1'],
				],
			],
		]);

		$collection = $api->contacts()->createCollection([
			['name' => 'A'],
			['name' => 'B'],
		]);
		$this->assertTrue($collection->save());
		$this->assertSame(1, $collection->first()->id);

		$empty = new Contacts([]);
		$this->assertFalse($empty->save());
	}

	public function testLeadPipelineAndStatus(): void
	{
		$api = $this->makeApiClient();
		$storage = $this->seedCache($api);

		$pipeline = $api->pipelines()->create([
			'id' => 5,
			'name' => 'Main',
			'_embedded' => [
				'statuses' => [
					(object) ['id' => 50, 'name' => 'New', 'sort' => 10, 'pipeline_id' => 5],
				],
			],
		]);
		$storage->set('pipelines', new Pipelines([$pipeline]), 60);

		/** @var Lead $lead */
		$lead = $api->leads()->create([
			'id' => 100,
			'name' => 'L',
			'pipeline_id' => 5,
			'status_id' => 50,
		]);
		$this->assertSame(5, $lead->pipeline()->id);
		$this->assertSame(50, $lead->status()->id);
	}
}
