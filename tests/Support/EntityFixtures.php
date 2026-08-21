<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Support;

use Ufee\AmoV4\Collections;
use Ufee\AmoV4\Models;

/**
 * Метаданные сущностей для параметризованных unit-тестов.
 */
final class EntityFixtures
{
	/**
	 * Сервисы с create()/createCollection() и фиксированным api_path.
	 *
	 * @return array<string, array{
	 *   method: string,
	 *   args: array,
	 *   api_path: string,
	 *   entity_key: string,
	 *   model: class-string,
	 *   collection: class-string
	 * }>
	 */
	public static function creatable(): array
	{
		return [
			'contacts' => [
				'method' => 'contacts',
				'args' => [],
				'api_path' => '/api/v4/contacts',
				'entity_key' => 'contacts',
				'model' => Models\Contact::class,
				'collection' => Collections\Contacts::class,
			],
			'companies' => [
				'method' => 'companies',
				'args' => [],
				'api_path' => '/api/v4/companies',
				'entity_key' => 'companies',
				'model' => Models\Company::class,
				'collection' => Collections\Companies::class,
			],
			'leads' => [
				'method' => 'leads',
				'args' => [],
				'api_path' => '/api/v4/leads',
				'entity_key' => 'leads',
				'model' => Models\Lead::class,
				'collection' => Collections\Leads::class,
			],
			'unsorted' => [
				'method' => 'unsorted',
				'args' => [],
				'api_path' => '/api/v4/leads/unsorted',
				'entity_key' => 'unsorted',
				'model' => Models\Unsorted::class,
				'collection' => Collections\Unsorteds::class,
			],
			'customers' => [
				'method' => 'customers',
				'args' => [],
				'api_path' => '/api/v4/customers',
				'entity_key' => 'customers',
				'model' => Models\Customer::class,
				'collection' => Collections\Customers::class,
			],
			'tasks' => [
				'method' => 'tasks',
				'args' => [],
				'api_path' => '/api/v4/tasks',
				'entity_key' => 'tasks',
				'model' => Models\Task::class,
				'collection' => Collections\Tasks::class,
			],
			'catalogs' => [
				'method' => 'catalogs',
				'args' => [],
				'api_path' => '/api/v4/catalogs',
				'entity_key' => 'catalogs',
				'model' => Models\Catalog::class,
				'collection' => Collections\Catalogs::class,
			],
			'catalogElements' => [
				'method' => 'catalogElements',
				'args' => [10],
				'api_path' => '/api/v4/catalogs/10/elements',
				'entity_key' => 'elements',
				'model' => Models\CatalogElement::class,
				'collection' => Collections\CatalogElements::class,
			],
			'lossReasons' => [
				'method' => 'lossReasons',
				'args' => [],
				'api_path' => '/api/v4/leads/loss_reasons',
				'entity_key' => 'loss_reasons',
				'model' => Models\LossReason::class,
				'collection' => Collections\LossReasons::class,
			],
			'users' => [
				'method' => 'users',
				'args' => [],
				'api_path' => '/api/v4/users',
				'entity_key' => 'users',
				'model' => Models\User::class,
				'collection' => Collections\Users::class,
			],
			'sources' => [
				'method' => 'sources',
				'args' => [],
				'api_path' => '/api/v4/sources',
				'entity_key' => 'sources',
				'model' => Models\Source::class,
				'collection' => Collections\Sources::class,
			],
			'customerSegments' => [
				'method' => 'customerSegments',
				'args' => [],
				'api_path' => '/api/v4/customers/segments',
				'entity_key' => 'segments',
				'model' => Models\CustomerSegment::class,
				'collection' => Collections\CustomerSegments::class,
			],
			'pipelines' => [
				'method' => 'pipelines',
				'args' => [],
				'api_path' => '/api/v4/leads/pipelines',
				'entity_key' => 'pipelines',
				'model' => Models\Pipeline::class,
				'collection' => Collections\Pipelines::class,
			],
			'pipelineStatuses' => [
				'method' => 'pipelineStatuses',
				'args' => [5],
				'api_path' => '/api/v4/leads/pipelines/5/statuses',
				'entity_key' => 'statuses',
				'model' => Models\PipelineStatus::class,
				'collection' => Collections\PipelineStatuses::class,
			],
			'events' => [
				'method' => 'events',
				'args' => [],
				'api_path' => '/api/v4/events',
				'entity_key' => 'events',
				'model' => Models\Event::class,
				'collection' => Collections\Events::class,
			],
			'bots' => [
				'method' => 'bots',
				'args' => [],
				'api_path' => '/api/v4/bots',
				'entity_key' => 'items',
				'model' => Models\Bot::class,
				'collection' => Collections\Bots::class,
			],
			'agents' => [
				'method' => 'agents',
				'args' => [],
				'api_path' => '/api/v4/amma/agents',
				'entity_key' => 'agents',
				'model' => Models\Agent::class,
				'collection' => Collections\Agents::class,
			],
			'widgets' => [
				'method' => 'widgets',
				'args' => [],
				'api_path' => '/api/v4/widgets',
				'entity_key' => 'widgets',
				'model' => Models\Widget::class,
				'collection' => Collections\Widgets::class,
			],
			'webhooks' => [
				'method' => 'webhooks',
				'args' => [],
				'api_path' => '/api/v4/webhooks',
				'entity_key' => 'webhooks',
				'model' => Models\Webhook::class,
				'collection' => Collections\Webhooks::class,
			],
			'notes' => [
				'method' => 'notes',
				'args' => ['contacts'],
				'api_path' => '/api/v4/contacts/notes',
				'entity_key' => 'notes',
				'model' => Models\Note::class,
				'collection' => Collections\Notes::class,
			],
			'links' => [
				'method' => 'links',
				'args' => ['contacts', 100],
				'api_path' => '/api/v4/contacts/100',
				'entity_key' => 'links',
				'model' => Models\Link::class,
				'collection' => Collections\Links::class,
			],
			'customFields' => [
				'method' => 'customFields',
				'args' => ['contacts'],
				'api_path' => '/api/v4/contacts/custom_fields',
				'entity_key' => 'custom_fields',
				'model' => Models\AccountCfield::class,
				'collection' => Collections\CustomFields::class,
			],
			'subscriptions' => [
				'method' => 'subscriptions',
				'args' => ['leads', 77],
				'api_path' => '/api/v4/leads/77/subscriptions',
				'entity_key' => 'subscriptions',
				'model' => Models\Subscription::class,
				'collection' => Collections\Subscriptions::class,
			],
			'talks' => [
				'method' => 'talks',
				'args' => [],
				'api_path' => '/api/v4/talks',
				'entity_key' => 'talks',
				'model' => Models\Talk::class,
				'collection' => Collections\Talks::class,
			],
			'files' => [
				'method' => 'files',
				'args' => [],
				'api_path' => '/v1.0/files',
				'entity_key' => 'files',
				'model' => Models\File::class,
				'collection' => Collections\Files::class,
			],
		];
	}

	/** @return array<string, array{0: string, 1: array, 2: class-string, 3: class-string}> */
	public static function createProvider(): array
	{
		$out = [];
		foreach (self::creatable() as $name => $meta) {
			$out[$name] = [$meta['method'], $meta['args'], $meta['model'], $meta['collection']];
		}
		return $out;
	}

	/** @return array<string, array{0: string, 1: array, 2: string, 3: string}> */
	public static function metaProvider(): array
	{
		$out = [];
		foreach (self::creatable() as $name => $meta) {
			$out[$name] = [$meta['method'], $meta['args'], $meta['api_path'], $meta['entity_key']];
		}
		return $out;
	}

	/** Сущности с WithCfield (+ опционально Tags). */
	public static function withCfieldProvider(): array
	{
		return [
			'contacts' => ['contacts', [], true],
			'companies' => ['companies', [], true],
			'leads' => ['leads', [], true],
			'customers' => ['customers', [], true],
			'catalogElements' => ['catalogElements', [10], false],
		];
	}

	/** Сущности с lazy Tasks/Notes на модели. */
	public static function tasksNotesProvider(): array
	{
		return [
			'contacts' => ['contacts', []],
			'companies' => ['companies', []],
			'leads' => ['leads', []],
			'customers' => ['customers', []],
		];
	}

	public static function searchByNameProvider(): array
	{
		return [
			'contacts' => ['contacts', []],
			'companies' => ['companies', []],
			'leads' => ['leads', []],
			'customers' => ['customers', []],
			'catalogElements' => ['catalogElements', [10]],
		];
	}

	public static function searchByEmailPhoneProvider(): array
	{
		return [
			'contacts' => ['contacts', []],
			'companies' => ['companies', []],
		];
	}

	public static function searchByCustomFieldProvider(): array
	{
		return [
			'contacts' => ['contacts', []],
			'companies' => ['companies', []],
			'leads' => ['leads', []],
		];
	}
}
