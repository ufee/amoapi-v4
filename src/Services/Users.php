<?php
/**
 * amoCRM API client Users service
 */
namespace Ufee\AmoV4\Services;

class Users extends Service
{
	/**
	 * Добавляет в ответ роль к которой принадлежит пользователь
	 */
	public const ROLE = 'role';

	/**
	 * Добавляет в ответ группу к которой принадлежит пользователь
	 */
	public const GROUP = 'group';

	/**
	 * Добавляет в ответ UUID пользователя, может быть null, в данный момент uuid не используется для работы сторонних интеграций
	 */
	public const UUID = 'uuid';

	/**
	 * Добавляет в ответ ID пользователя в сервисе чатов, может быть null
	 */
	public const AMOJO_ID = 'amojo_id';

	/**
	 * Добавляет в ответ ранг пользователя. Возможные варианты: newbie candidate master
	 */
	public const USER_RANK = 'user_rank';

	/**
	 * Добавляет в ответ номер пользователя
	 */
	public const PHONE_NUMBER = 'phone_number';

	protected $api_path = '/api/v4/users';
	protected $entity_key = 'users';

	protected $entity_model = '\Ufee\AmoV4\Models\User';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Users';

    /**
     * Service on load
	 * @return void
     */
	protected function _boot()
	{
		$this->query_args['with'] = implode(',', [self::UUID, self::AMOJO_ID, self::PHONE_NUMBER]);
	}

	/**
	 * Все with-параметры для обогащения ответа
	 * @return string[]
	 */
	public static function withValues(): array
	{
		return [
			self::ROLE,
			self::GROUP,
			self::UUID,
			self::AMOJO_ID,
			self::USER_RANK,
			self::PHONE_NUMBER,
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
}
