<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Services;

use Ufee\AmoV4\Models\Talk;
use Ufee\AmoV4\Models\TalkMessage;
use Ufee\AmoV4\Services\TalkMessages;
use Ufee\AmoV4\Services\Talks;
use Ufee\AmoV4\Tests\TestCase;

class TalksTest extends TestCase
{
	public function testServiceMeta(): void
	{
		$service = $this->service('talks');
		$this->assertInstanceOf(Talks::class, $service);
		$this->assertSame('/api/v4/talks', $service->api_path);
		$this->assertSame('talks', $service->entity_key);
		$this->assertSame(
			[
				Talks::STATUS_IN_WORK,
				Talks::STATUS_CLOSED,
				Talks::STATUS_NPS_SCHEDULED,
				Talks::STATUS_NPS_IN_PROGRESS,
				Talks::STATUS_WITH_ERROR,
			],
			Talks::statusValues()
		);
		$this->assertSame(
			[Talks::ENTITY_LEAD, Talks::ENTITY_CUSTOMER],
			Talks::entityTypeValues()
		);
	}

	public function testCreateModel(): void
	{
		$talk = $this->service('talks')->create([
			'talk_id' => 117,
			'status' => Talks::STATUS_IN_WORK,
			'is_in_work' => true,
			'is_read' => false,
		]);
		$this->assertInstanceOf(Talk::class, $talk);
		$this->assertSame(117, $talk->talk_id);
		$this->assertTrue($talk->isInWork());
		$this->assertFalse($talk->isRead());
	}

	public function testModelHelpersWithoutFields(): void
	{
		$talk = $this->service('talks')->create(['talk_id' => 1]);
		$this->assertNull($talk->isInWork());
		$this->assertNull($talk->isRead());
	}

	public function testCloseRejectsNonPositiveId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Talk ID must be positive integer');
		$this->service('talks')->close(0);
	}

	public function testModelCloseDelegatesToService(): void
	{
		$service = $this->getMockBuilder(Talks::class)
			->disableOriginalConstructor()
			->onlyMethods(['close'])
			->getMock();

		$service->expects($this->once())
			->method('close')
			->with(117, true)
			->willReturn(true);

		$talk = new Talk(['talk_id' => 117], $service);
		$this->assertTrue($talk->close(true));
	}

	public function testModelCloseRejectsMissingId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Talk ID must be positive integer');

		$talk = $this->service('talks')->create([]);
		$talk->close();
	}

	public function testMessagesServicePath(): void
	{
		$messages = $this->service('talks')->messages(123);
		$this->assertInstanceOf(TalkMessages::class, $messages);
		$this->assertSame('/api/v4/talks/123/messages', $messages->api_path);
		$this->assertSame('messages', $messages->entity_key);
	}

	public function testMessagesRejectsNonPositiveId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Talk ID must be positive integer');
		$this->service('talks')->messages(0);
	}

	public function testTalkMessagesConstants(): void
	{
		$this->assertSame(
			[TalkMessages::TYPE_INCOMING, TalkMessages::TYPE_OUTGOING],
			TalkMessages::typeValues()
		);
		$this->assertContains(TalkMessages::MESSAGE_TEXT, TalkMessages::messageTypeValues());
		$this->assertContains(TalkMessages::AUTHOR_BOT, TalkMessages::authorTypeValues());
		$this->assertSame(
			[
				TalkMessages::DELIVERY_SENT,
				TalkMessages::DELIVERY_DELIVERED,
				TalkMessages::DELIVERY_ERROR,
			],
			TalkMessages::deliveryStatusValues()
		);
	}

	public function testTalkMessageHelpers(): void
	{
		$message = $this->service('talks')->messages(1)->create([
			'id' => 'msg-1',
			'type' => TalkMessages::TYPE_INCOMING,
			'message_type' => TalkMessages::MESSAGE_TEXT,
			'text' => 'Hello',
			'attachment' => null,
		]);
		$this->assertInstanceOf(TalkMessage::class, $message);
		$this->assertTrue($message->isIncoming());
		$this->assertFalse($message->isOutgoing());
		$this->assertFalse($message->hasAttachment());

		$withFile = $this->service('talks')->messages(1)->create([
			'type' => TalkMessages::TYPE_OUTGOING,
			'attachment' => (object)['type' => 'picture', 'link' => 'https://example.com/a.png'],
		]);
		$this->assertTrue($withFile->isOutgoing());
		$this->assertTrue($withFile->hasAttachment());
	}

	public function testModelMessagesHelperBuildsService(): void
	{
		$talk = $this->service('talks')->create(['talk_id' => 55]);
		$service = $talk->messages();
		$this->assertInstanceOf(TalkMessages::class, $service);
		$this->assertSame('/api/v4/talks/55/messages', $service->api_path);
	}

	public function testTalkMessagesRequiresTalkId(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('required talk_id');
		new TalkMessages($this->makeApiClient(), []);
	}
}
