<?php
/**
 * amoCRM Unsorted model
 */
namespace Ufee\AmoV4\Models;

class Unsorted extends Model
{
	/**
	 * Unsorted is created via addSip()/addForms(), not standard save()
	 * @return bool
	 */
	public function save()
	{
		throw new \RuntimeException('Use Unsorted service addSip()/addForms() to create unsorted leads');
	}

	/**
	 * Accept this unsorted
	 * @param array $data - optional user_id, status_id
	 * @return Unsorted
	 */
	public function accept(array $data = [])
	{
		return $this->service->accept($this->uid, $data);
	}

	/**
	 * Decline this unsorted
	 * @param array $data - optional user_id
	 * @return Unsorted
	 */
	public function decline(array $data = [])
	{
		return $this->service->decline($this->uid, $data);
	}

	/**
	 * Link this unsorted (chats only) to existing lead or customer
	 * @param array $link - entity_id, entity_type, optional metadata
	 * @param int|null $user_id
	 * @return Unsorted
	 */
	public function link(array $link, ?int $user_id = null)
	{
		return $this->service->link($this->uid, $link, $user_id);
	}
}
