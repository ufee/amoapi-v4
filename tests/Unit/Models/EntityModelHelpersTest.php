<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Models;

use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Models\Customer;
use Ufee\AmoV4\Models\File;
use Ufee\AmoV4\Models\Lead;
use Ufee\AmoV4\Models\Note;
use Ufee\AmoV4\Models\Task;
use Ufee\AmoV4\Services\Notes as NotesService;
use Ufee\AmoV4\Tests\Support\EntityFixtures;
use Ufee\AmoV4\Tests\TestCase;

class EntityModelHelpersTest extends TestCase
{
	/**
	 * @dataProvider tasksNotesProvider
	 */
	public function testCreateTaskAndNoteAreLazy(string $method, array $args): void
	{
		$model = $this->service($method, ...$args)->create(['id' => 50, 'name' => 'Unit']);

		$task = $model->createTask(2);
		$this->assertInstanceOf(Task::class, $task);
		$this->assertSame(50, $task->entity_id);
		$this->assertSame($model->service->entity_key, $task->entity_type);
		$this->assertSame(2, $task->task_type_id);

		$note = $model->createNote('common');
		$this->assertInstanceOf(Note::class, $note);
		$this->assertSame(50, $note->entity_id);
		$this->assertSame($model->service->entity_key, $note->entity_type);
		$this->assertSame('common', $note->note_type);
	}

	public function testTaskSetCompleted(): void
	{
		$task = $this->service('tasks')->create([
			'id' => 1,
			'text' => 'Do',
			'complete_till' => time(),
		]);
		$task->setCompleted(true, 'done');

		$this->assertTrue($task->is_completed);
		$this->assertSame(['text' => 'done'], $task->result);
	}

	public function testNoteSetParamsAndPinnedState(): void
	{
		$note = $this->service('notes', 'contacts')->create([
			'id' => 3,
			'note_type' => 'common',
			'params' => [],
			'is_pinned' => false,
		]);
		$note->setParams(['text' => 'hello']);
		$this->assertSame(['text' => 'hello'], $note->params);
		$this->assertFalse($note->isPinned());

		$service = $this->getMockBuilder(NotesService::class)
			->disableOriginalConstructor()
			->onlyMethods(['pin', 'unpin'])
			->getMock();
		$service->expects($this->once())->method('pin')->with(3)->willReturn(true);

		$notePinned = new Note([
			'id' => 3,
			'note_type' => 'common',
			'params' => ['text' => 'x'],
			'is_pinned' => false,
		], $service);
		$notePinned->pin();
		$this->assertTrue($notePinned->is_pinned);
	}

	public function testCustomerSetSegments(): void
	{
		/** @var Customer $customer */
		$customer = $this->service('customers')->create(['name' => 'C']);
		$customer->setSegments([10, 20]);

		$payload = $customer->getChangedRawData();
		$this->assertSame([['id' => 10], ['id' => 20]], $payload->_embedded['segments']);
	}

	public function testLeadHasCompanyAndMainContactFlags(): void
	{
		/** @var Lead $lead */
		$lead = $this->service('leads')->create([
			'id' => 1,
			'name' => 'L',
			'_embedded' => [
				'companies' => [(object) ['id' => 9]],
				'contacts' => [(object) ['id' => 8, 'is_main' => true]],
			],
		]);

		$this->assertTrue($lead->hasCompany());
		$this->assertSame(9, $lead->getCompanyId());
		$this->assertTrue($lead->hasMainContact());
	}

	public function testFileDownloadUrlsAndSaveGuard(): void
	{
		$file = $this->service('files')->create([
			'uuid' => 'u-1',
			'name' => 'a.txt',
			'_links' => [
				'download' => (object) ['href' => 'https://drive.example/d'],
				'download_version' => ['href' => 'https://drive.example/v'],
			],
		]);

		$this->assertInstanceOf(File::class, $file);
		$this->assertSame('https://drive.example/d', $file->getDownloadUrl());
		$this->assertSame('https://drive.example/v', $file->getDownloadVersionUrl());

		$empty = $this->service('files')->create(['name' => 'x']);
		$this->expectException(\Ufee\AmoV4\Exceptions\AmoException::class);
		$this->expectExceptionMessage('use files()->upload()');
		$empty->save();
	}

	public function testNormalizeFileUuidsOnContact(): void
	{
		/** @var Contact $contact */
		$contact = $this->service('contacts')->create(['id' => 1, 'name' => 'C']);
		$file = $this->service('files')->create(['uuid' => 'file-uuid-1']);

		$ref = new \ReflectionMethod($contact, 'normalizeFileUuids');
		$ref->setAccessible(true);

		$this->assertSame(['abc'], $ref->invoke($contact, 'abc'));
		$this->assertSame(['file-uuid-1'], $ref->invoke($contact, $file));
		$this->assertSame(
			['a', 'b'],
			$ref->invoke($contact, [['uuid' => 'a'], ['file_uuid' => 'b']])
		);
	}

	public function testAccountModelBootsEmbeddedCollections(): void
	{
		$account = new \Ufee\AmoV4\Models\Account([
			'id' => 1,
			'name' => 'Acc',
			'_embedded' => [
				'users_groups' => [(object) ['id' => 0, 'name' => 'Admins']],
				'task_types' => [(object) ['id' => 1, 'name' => 'Call']],
			],
		], $this->service('account'));

		$this->assertCount(1, $account->userGroups);
		$this->assertCount(1, $account->taskTypes);
		$this->assertSame('Admins', $account->userGroups->first()->name);
	}

	public function tasksNotesProvider(): array
	{
		return EntityFixtures::tasksNotesProvider();
	}
}
