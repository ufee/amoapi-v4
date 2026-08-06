<?php
/**
 * amoCRM API client Account service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\Models;

class Account extends Service
{
	/**
	 * Добавляет в ответ ID аккаунта в сервисе чатов
	 */
	public const AMOJO_ID = 'amojo_id';

	/**
	 * Добавляет в ответ информацию о доступности функционала создания групповых и использования директ чатов пользователями
	 */
	public const AMOJO_RIGHTS = 'amojo_rights';

	/**
	 * Добавляет в ответ информацию о доступных группах пользователей аккаунта
	 */
	public const USERS_GROUPS = 'users_groups';

	/**
	 * Добавляет в ответ информацию о доступных типах задач в аккаунте
	 */
	public const TASK_TYPES = 'task_types';

	/**
	 * Добавляет в ответ информацию о текущей версии amoCRM
	 */
	public const VERSION = 'version';

	/**
	 * Добавляет в ответ названия сущностей с их переводами и формами чисел
	 */
	public const ENTITY_NAMES = 'entity_names';

	/**
	 * Добавляет в ответ информацию о текущих настройках форматов даты и времени аккаунта
	 */
	public const DATETIME_SETTINGS = 'datetime_settings';

	/**
	 * Добавляет в ответ адрес сервиса файлов для запрашиваемого аккаунта
	 */
	public const DRIVE_URL = 'drive_url';

	/**
	 * Добавляет в ответ флаг, включена ли Альфа-фильтрация для аккаунта
	 */
	public const IS_API_FILTER_ENABLED = 'is_api_filter_enabled';

	/**
	 * Добавляет в ответ информацию о настройки счетов-покупок
	 */
	public const INVOICES_SETTINGS = 'invoices_settings';

	protected $api_path = '/api/v4/account';
	protected $entity_key = 'account';

    /**
     * Service on load
	 * @return void
     */
	protected function _boot()
	{
		$this->query_args['with'] = implode(',', [
			self::AMOJO_ID,
			self::USERS_GROUPS,
			self::TASK_TYPES,
			self::VERSION,
			self::DATETIME_SETTINGS,
			self::DRIVE_URL,
			self::IS_API_FILTER_ENABLED,
		]);
	}

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::AMOJO_ID,
			self::AMOJO_RIGHTS,
			self::USERS_GROUPS,
			self::TASK_TYPES,
			self::VERSION,
			self::ENTITY_NAMES,
			self::DATETIME_SETTINGS,
			self::DRIVE_URL,
			self::IS_API_FILTER_ENABLED,
			self::INVOICES_SETTINGS,
		];
	}

	/**
	 * Установить все with-параметры обогащения ответа
	 * @return static
	 */
	public function withAll()
	{
		return $this->with(static::withValues());
	}
	
    /**
     * Get account info
	 * @param array $with
	 * @return Models\Account|null
     */
	public function get($with = null)
	{
		if (is_null($with)) {
			$with = [];
		}
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->execute();
		$row = $query->response->validated();
		
		return new Models\Account((array)$row, $this);
	}
}
