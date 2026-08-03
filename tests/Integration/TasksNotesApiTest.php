<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Integration;

use Ufee\AmoV4\Models\Note;
use Ufee\AmoV4\Models\Task;

/**
 * @group integration
 */
class TasksNotesApiTest extends IntegrationTestCase
{
	public function testCreateTaskAndNoteForLead(): void
	{
		$lead = $this->api->leads()->create(['name' => $this->uniqueName('Lead for task')]);
		$this->assertTrue($lead->save());
		$this->trackDelete('/api/v4/leads', (int) $lead->id);

		$task = $lead->createTask(1);
		$task->text = 'ITEST task ' . uniqid('', false);
		$task->complete_till = time() + 3600;
		$this->assertTrue($task->save(), 'Не удалось создать задачу');
		$this->assertInstanceOf(Task::class, $task);
		$this->assertNotEmpty($task->id);
		$this->trackDelete('/api/v4/tasks', (int) $task->id);

		$foundTask = $this->api->tasks()->find($task->id);
		$this->assertInstanceOf(Task::class, $foundTask);
		$this->assertSame((int) $lead->id, (int) $foundTask->entity_id);

		$foundTask->setCompleted(true, 'ITEST done');
		$this->assertTrue($foundTask->save(), 'Не удалось завершить задачу');
		$reloadedTask = $this->api->tasks()->find($task->id);
		$this->assertTrue((bool) $reloadedTask->is_completed);

		$note = $lead->createNote('common');
		$note->setParams(['text' => 'ITEST note ' . uniqid('', false)]);
		$this->assertTrue($note->save(), 'Не удалось создать примечание');
		$this->assertInstanceOf(Note::class, $note);
		$this->assertNotEmpty($note->id);
		$this->trackDelete('/api/v4/leads/notes', (int) $note->id);

		$foundNote = $this->api->notes('leads', $lead->id)->find($note->id);
		$this->assertInstanceOf(Note::class, $foundNote);
		$this->assertSame('common', $foundNote->note_type);
	}
}
