<?php
/**
 * amoCRM Talk Message model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services\TalkMessages;

class TalkMessage extends Model
{
	/**
	 * Is incoming message
	 * @return bool|null
	 */
	public function isIncoming()
	{
		if ($this->hasField('type')) {
			return $this->type === TalkMessages::TYPE_INCOMING;
		}
		return null;
	}

	/**
	 * Is outgoing message
	 * @return bool|null
	 */
	public function isOutgoing()
	{
		if ($this->hasField('type')) {
			return $this->type === TalkMessages::TYPE_OUTGOING;
		}
		return null;
	}

	/**
	 * Has attachment
	 * @return bool
	 */
	public function hasAttachment()
	{
		return !empty($this->attachment);
	}
}
