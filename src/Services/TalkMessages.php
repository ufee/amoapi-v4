<?php
/**
 * amoCRM API client Talk Messages service
 * GET /api/v4/talks/{talk_id}/messages
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class TalkMessages extends Service
{
	/**
	 * Входящее сообщение
	 */
	public const TYPE_INCOMING = 'incoming';

	/**
	 * Исходящее сообщение
	 */
	public const TYPE_OUTGOING = 'outgoing';

	/**
	 * Автор — пользователь CRM
	 */
	public const AUTHOR_INTERNAL = 'internal';

	/**
	 * Автор — клиент
	 */
	public const AUTHOR_EXTERNAL = 'external';

	/**
	 * Автор — бот
	 */
	public const AUTHOR_BOT = 'bot';

	/**
	 * Тип контента — текст
	 */
	public const MESSAGE_TEXT = 'text';

	/**
	 * Тип контента — контакт
	 */
	public const MESSAGE_CONTACT = 'contact';

	/**
	 * Тип контента — файл
	 */
	public const MESSAGE_FILE = 'file';

	/**
	 * Тип контента — видео
	 */
	public const MESSAGE_VIDEO = 'video';

	/**
	 * Тип контента — изображение
	 */
	public const MESSAGE_PICTURE = 'picture';

	/**
	 * Тип контента — голосовое
	 */
	public const MESSAGE_VOICE = 'voice';

	/**
	 * Тип контента — аудио
	 */
	public const MESSAGE_AUDIO = 'audio';

	/**
	 * Тип контента — стикер
	 */
	public const MESSAGE_STICKER = 'sticker';

	/**
	 * Тип контента — геолокация
	 */
	public const MESSAGE_LOCATION = 'location';

	/**
	 * Статус доставки — отправлено
	 */
	public const DELIVERY_SENT = 'sent';

	/**
	 * Статус доставки — доставлено
	 */
	public const DELIVERY_DELIVERED = 'delivered';

	/**
	 * Статус доставки — ошибка
	 */
	public const DELIVERY_ERROR = 'error';

	protected $api_path = '/api/v4/talks/{id}/messages';
	protected $entity_key = 'messages';

	protected $entity_model = '\Ufee\AmoV4\Models\TalkMessage';
	protected $entity_collection = '\Ufee\AmoV4\Collections\TalkMessages';

	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $args — [talk_id]
	 */
	public function __construct(ApiClient $client, array $args)
	{
		$this->client_id = $client->client_id;

		if (count($args) < 1) {
			throw new \InvalidArgumentException('TalkMessages Service required talk_id argument');
		}
		$talk_id = $args[0];
		if (!is_int($talk_id) && !(is_string($talk_id) && ctype_digit($talk_id))) {
			throw new \InvalidArgumentException('TalkMessages Service talk_id must be integer/string');
		}
		$talk_id = (int)$talk_id;
		if ($talk_id <= 0) {
			throw new \InvalidArgumentException('Talk ID must be positive integer');
		}

		$this->api_path = str_replace('{id}', (string)$talk_id, $this->api_path);
		$this->_boot();
	}

	/**
	 * Все значения type (направление)
	 * @return string[]
	 */
	public static function typeValues(): array
	{
		return [
			self::TYPE_INCOMING,
			self::TYPE_OUTGOING,
		];
	}

	/**
	 * Все значения message_type
	 * @return string[]
	 */
	public static function messageTypeValues(): array
	{
		return [
			self::MESSAGE_TEXT,
			self::MESSAGE_CONTACT,
			self::MESSAGE_FILE,
			self::MESSAGE_VIDEO,
			self::MESSAGE_PICTURE,
			self::MESSAGE_VOICE,
			self::MESSAGE_AUDIO,
			self::MESSAGE_STICKER,
			self::MESSAGE_LOCATION,
		];
	}

	/**
	 * Все значения author[type]
	 * @return string[]
	 */
	public static function authorTypeValues(): array
	{
		return [
			self::AUTHOR_INTERNAL,
			self::AUTHOR_EXTERNAL,
			self::AUTHOR_BOT,
		];
	}

	/**
	 * Все значения delivery_status
	 * @return string[]
	 */
	public static function deliveryStatusValues(): array
	{
		return [
			self::DELIVERY_SENT,
			self::DELIVERY_DELIVERED,
			self::DELIVERY_ERROR,
		];
	}
}
