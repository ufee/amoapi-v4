<?php
/**
 * amoCRM API client Unsorted service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\Models;

class Unsorted extends Service
{
	protected $api_path = '/api/v4/leads/unsorted';
	protected $entity_key = 'unsorted';

	protected $entity_model = '\Ufee\AmoV4\Models\Unsorted';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Unsorteds';

	/** @var array */
	protected $allowed_link_entity_types = ['leads', 'customers'];

	/**
	 * Find unsorted by UID
	 * @param string|int|array $uid
	 * @param array $with
	 * @return Models\Unsorted|\Ufee\AmoV4\Collections\Collection|null
	 */
	public function find($uid, $with = [])
	{
		if (is_array($uid)) {
			return $this->filter(['uid' => $uid], $with)->fetchAll();
		}
		if (!is_string($uid) || $uid === '') {
			throw new \InvalidArgumentException('Unsorted UID must be non-empty string');
		}
		return parent::find($uid, $with);
	}

	/**
	 * Standard add is not supported — use addSip()/addForms()
	 * @param array|object $data
	 */
	public function add($data)
	{
		throw new \RuntimeException('Use Unsorted::addSip() or Unsorted::addForms() instead of add()');
	}

	/**
	 * Unsorted entities are not updated via API
	 * @param int|array $elem_id
	 * @param object|null $data
	 */
	public function update($elem_id, $data = null)
	{
		throw new \RuntimeException('Unsorted entities can not be updated');
	}

	/**
	 * Add unsorted of sip (call) category
	 * @param array|object $data
	 * @return array|object|null
	 */
	public function addSip($data)
	{
		return $this->addByCategory('sip', $data);
	}

	/**
	 * Add unsorted of forms category
	 * @param array|object $data
	 * @return array|object|null
	 */
	public function addForms($data)
	{
		return $this->addByCategory('forms', $data);
	}

	/**
	 * Accept unsorted by UID
	 * @param string $uid
	 * @param array $data - optional user_id, status_id
	 * @return Models\Unsorted
	 */
	public function accept(string $uid, array $data = [])
	{
		$this->assertUid($uid);
		$query = $this->instance->query('POST', $this->api_path.'/'.$uid.'/accept');
		if (!empty($data)) {
			$query->setJsonData($data);
		}
		$query->execute();
		$row = $query->response->validated();
		return new Models\Unsorted((array)$row, $this);
	}

	/**
	 * Decline unsorted by UID
	 * @param string $uid
	 * @param array $data - optional user_id
	 * @return Models\Unsorted
	 */
	public function decline(string $uid, array $data = [])
	{
		$this->assertUid($uid);
		$query = $this->instance->query('DELETE', $this->api_path.'/'.$uid.'/decline');
		if (!empty($data)) {
			$query->setJsonData($data);
		}
		$query->execute();
		$row = $query->response->validated();
		return new Models\Unsorted((array)$row, $this);
	}

	/**
	 * Link unsorted (chats only) to existing lead or customer
	 * @param string $uid
	 * @param array $link - entity_id, entity_type, optional metadata
	 * @param int|null $user_id
	 * @return Models\Unsorted
	 */
	public function link(string $uid, array $link, ?int $user_id = null)
	{
		$this->assertUid($uid);
		if (empty($link['entity_id']) || (!is_int($link['entity_id']) && !(is_string($link['entity_id']) && ctype_digit($link['entity_id'])))) {
			throw new \InvalidArgumentException('Link entity_id must be positive integer');
		}
		if (empty($link['entity_type']) || !is_string($link['entity_type']) || !in_array($link['entity_type'], $this->allowed_link_entity_types, true)) {
			throw new \InvalidArgumentException('Link entity_type must be one of: leads, customers');
		}
		$payload = ['link' => $link];
		if (!is_null($user_id)) {
			if ($user_id <= 0) {
				throw new \InvalidArgumentException('User ID must be positive integer');
			}
			$payload['user_id'] = $user_id;
		}
		$query = $this->instance->query('POST', $this->api_path.'/'.$uid.'/link');
		$query->setJsonData($payload);
		$query->execute();
		$row = $query->response->validated();
		return new Models\Unsorted((array)$row, $this);
	}

	/**
	 * Get unsorted summary
	 * @param array $filter
	 * @return object
	 */
	public function summary(array $filter = [])
	{
		$query = $this->instance->query('GET', $this->api_path.'/summary');
		if (!empty($filter)) {
			$query->setArgs(['filter' => $filter]);
		}
		$query->execute();
		return $query->response->validated();
	}

	/**
	 * Add unsorted by category endpoint
	 * @param string $category
	 * @param array|object $data
	 * @return array|object|null
	 */
	protected function addByCategory(string $category, $data)
	{
		$single = false;
		if (is_object($data)) {
			$single = true;
			$payload = [$data];
		} else if (is_array($data)) {
			if ($this->isAssoc($data)) {
				$single = true;
				$payload = [$data];
			} else {
				$payload = $data;
			}
		} else {
			throw new \InvalidArgumentException('Unsorted data must be array or object');
		}
		if (empty($payload)) {
			throw new \InvalidArgumentException('Unsorted payload can not be empty');
		}

		$query = $this->instance->query('POST', $this->api_path.'/'.$category);
		$query->setJsonData($payload);
		$query->execute();
		$rows = $query->response->validatedCreatedEntities($this->entity_key);
		return $single ? current($rows) : $rows;
	}

	/**
	 * @param string $uid
	 */
	protected function assertUid(string $uid): void
	{
		if ($uid === '') {
			throw new \InvalidArgumentException('Unsorted UID can not be empty');
		}
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	protected function isAssoc(array $data): bool
	{
		if ($data === []) {
			return false;
		}
		return array_keys($data) !== range(0, count($data) - 1);
	}
}
