<?php
/**
 * amoCRM Talk model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services;

class Talk extends Model
{
	/**
	 * Close this talk (optionally force close without NPS bot)
	 * @param bool $force_close
	 * @return bool
	 */
	public function close(bool $force_close = false): bool
	{
		$talk_id = $this->normalizeTalkId($this->talk_id);
		return $this->service->close($talk_id, $force_close);
	}

	/**
	 * Get talk messages collection
	 * @param array $filter
	 * @return \Ufee\AmoV4\Collections\TalkMessages
	 */
	public function getMessages(array $filter = [])
	{
		$service = $this->messages();
		if (empty($filter)) {
			return $service->get();
		}
		return $service->filter($filter)->fetchAll();
	}

	/**
	 * Get talk messages service
	 * @return Services\TalkMessages
	 */
	public function messages()
	{
		return $this->service->messages($this->normalizeTalkId($this->talk_id));
	}

	/**
	 * Is talk in work (not closed)
	 * @return bool|null
	 */
	public function isInWork()
	{
		if ($this->hasField('is_in_work')) {
			return (bool) $this->is_in_work;
		}
		return null;
	}

	/**
	 * Is talk read
	 * @return bool|null
	 */
	public function isRead()
	{
		if ($this->hasField('is_read')) {
			return (bool) $this->is_read;
		}
		return null;
	}

	/**
	 * @param mixed $talk_id
	 * @return int
	 */
	protected function normalizeTalkId($talk_id): int
	{
		if (is_int($talk_id) && $talk_id > 0) {
			return $talk_id;
		}
		if (is_string($talk_id) && ctype_digit($talk_id) && (int)$talk_id > 0) {
			return (int)$talk_id;
		}
		throw new \InvalidArgumentException('Talk ID must be positive integer');
	}
}
