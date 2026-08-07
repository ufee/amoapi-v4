<?php
/**
 * amoCRM API client Webhooks service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Models;
use Ufee\AmoV4\Exceptions;

class Webhooks extends Service
{
	/**
	 * У сделки сменился ответственный
	 */
	public const EVENT_RESPONSIBLE_LEAD = 'responsible_lead';

	/**
	 * У контакта сменился ответственный
	 */
	public const EVENT_RESPONSIBLE_CONTACT = 'responsible_contact';

	/**
	 * У компании сменился ответственный
	 */
	public const EVENT_RESPONSIBLE_COMPANY = 'responsible_company';

	/**
	 * У покупателя сменился ответственный
	 */
	public const EVENT_RESPONSIBLE_CUSTOMER = 'responsible_customer';

	/**
	 * У задачи сменился ответственный
	 */
	public const EVENT_RESPONSIBLE_TASK = 'responsible_task';

	/**
	 * Сделка восстановлена из удаленных
	 */
	public const EVENT_RESTORE_LEAD = 'restore_lead';

	/**
	 * Контакт восстановлен из удаленных
	 */
	public const EVENT_RESTORE_CONTACT = 'restore_contact';

	/**
	 * Компания восстановлена из удаленных
	 */
	public const EVENT_RESTORE_COMPANY = 'restore_company';

	/**
	 * Добавлена сделка
	 */
	public const EVENT_ADD_LEAD = 'add_lead';

	/**
	 * Добавлен контакт
	 */
	public const EVENT_ADD_CONTACT = 'add_contact';

	/**
	 * Добавлена компания
	 */
	public const EVENT_ADD_COMPANY = 'add_company';

	/**
	 * Добавлен покупатель
	 */
	public const EVENT_ADD_CUSTOMER = 'add_customer';

	/**
	 * Добавлена беседа
	 */
	public const EVENT_ADD_TALK = 'add_talk';

	/**
	 * Добавлена задача
	 */
	public const EVENT_ADD_TASK = 'add_task';

	/**
	 * Сделка изменена
	 */
	public const EVENT_UPDATE_LEAD = 'update_lead';

	/**
	 * Контакт изменён
	 */
	public const EVENT_UPDATE_CONTACT = 'update_contact';

	/**
	 * Компания изменена
	 */
	public const EVENT_UPDATE_COMPANY = 'update_company';

	/**
	 * Покупатель изменен
	 */
	public const EVENT_UPDATE_CUSTOMER = 'update_customer';

	/**
	 * Беседа изменена
	 */
	public const EVENT_UPDATE_TALK = 'update_talk';

	/**
	 * Задача изменена
	 */
	public const EVENT_UPDATE_TASK = 'update_task';

	/**
	 * Удалена сделка
	 */
	public const EVENT_DELETE_LEAD = 'delete_lead';

	/**
	 * Удалён контакт
	 */
	public const EVENT_DELETE_CONTACT = 'delete_contact';

	/**
	 * Удалена компания
	 */
	public const EVENT_DELETE_COMPANY = 'delete_company';

	/**
	 * Удален покупатель
	 */
	public const EVENT_DELETE_CUSTOMER = 'delete_customer';

	/**
	 * Удалена задача
	 */
	public const EVENT_DELETE_TASK = 'delete_task';

	/**
	 * У сделки сменился статус
	 */
	public const EVENT_STATUS_LEAD = 'status_lead';

	/**
	 * Примечание добавлено в сделку
	 */
	public const EVENT_NOTE_LEAD = 'note_lead';

	/**
	 * Примечание добавлено в контакт
	 */
	public const EVENT_NOTE_CONTACT = 'note_contact';

	/**
	 * Примечание добавлено в компанию
	 */
	public const EVENT_NOTE_COMPANY = 'note_company';

	/**
	 * Примечание добавлено в покупателя
	 */
	public const EVENT_NOTE_CUSTOMER = 'note_customer';

	/**
	 * Получено входящее сообщение от клиента
	 */
	public const EVENT_ADD_MESSAGE = 'add_message';

	/**
	 * Отправлено исходящее сообщение из amoCRM клиенту
	 */
	public const EVENT_ADD_OUTGOING_MESSAGE = 'add_outgoing_message';

	/**
	 * Шаблон WhatsApp отправлен на одобрение
	 */
	public const EVENT_ADD_CHAT_TEMPLATE_REVIEW = 'add_chat_template_review';

	protected $api_path = '/api/v4/webhooks';
	protected $entity_key = 'webhooks';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Webhook';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Webhooks';

	/**
	 * Все значения settings (события вебхука)
	 * @return string[]
	 */
	public static function eventValues(): array
	{
		return [
			self::EVENT_RESPONSIBLE_LEAD,
			self::EVENT_RESPONSIBLE_CONTACT,
			self::EVENT_RESPONSIBLE_COMPANY,
			self::EVENT_RESPONSIBLE_CUSTOMER,
			self::EVENT_RESPONSIBLE_TASK,
			self::EVENT_RESTORE_LEAD,
			self::EVENT_RESTORE_CONTACT,
			self::EVENT_RESTORE_COMPANY,
			self::EVENT_ADD_LEAD,
			self::EVENT_ADD_CONTACT,
			self::EVENT_ADD_COMPANY,
			self::EVENT_ADD_CUSTOMER,
			self::EVENT_ADD_TALK,
			self::EVENT_ADD_TASK,
			self::EVENT_UPDATE_LEAD,
			self::EVENT_UPDATE_CONTACT,
			self::EVENT_UPDATE_COMPANY,
			self::EVENT_UPDATE_CUSTOMER,
			self::EVENT_UPDATE_TALK,
			self::EVENT_UPDATE_TASK,
			self::EVENT_DELETE_LEAD,
			self::EVENT_DELETE_CONTACT,
			self::EVENT_DELETE_COMPANY,
			self::EVENT_DELETE_CUSTOMER,
			self::EVENT_DELETE_TASK,
			self::EVENT_STATUS_LEAD,
			self::EVENT_NOTE_LEAD,
			self::EVENT_NOTE_CONTACT,
			self::EVENT_NOTE_COMPANY,
			self::EVENT_NOTE_CUSTOMER,
			self::EVENT_ADD_MESSAGE,
			self::EVENT_ADD_OUTGOING_MESSAGE,
			self::EVENT_ADD_CHAT_TEMPLATE_REVIEW,
		];
	}
	
    /**
     * Get webhooks subscribed
	 * @param string $destination - filter by url
	 * @return Collections\Webhooks
     */
	public function get($destination = null)
	{
		$query_args = $this->query_args;
		if (is_string($destination) && !empty($destination)) {
			$query_args['filter'] = ['destination' => $destination];
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->execute();

		$validated = $query->response->validated();
		if (!property_exists($validated, '_embedded')) {
			$code = $query->response->getCode();
			throw new Exceptions\AmoException('Invalid API response for entities, embedded not found, code: '.$code, $code);
		}
		if (property_exists($validated->_embedded, $this->entity_key)) {
			return $this->createCollection($validated->_embedded->{$this->entity_key});
		}
		return $this->createCollection();
	}
	
    /**
     * Subscribe to webhook
	 * @param string $url
	 * @param array $events
	 * @return Models\Webhook
     */
	public function subscribe(string $url, array $events)
	{
		$query = $this->instance->query('POST', $this->api_path);
		$query->setJsonData([
			'destination' => $url,
			'settings' => $events
		]);
		$query->execute();
		
		$result = $query->response->validated();
		return new Models\Webhook((array)$result, $this);
	}
	
    /**
     * Unsubscribe from webhook
	 * @param string $url
	 * @return bool
     */
    public function unsubscribe(string $url)
    {
		$query = $this->instance->query('DELETE', $this->api_path);
		$query->setJsonData([
			'destination' => $url
		]);
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
