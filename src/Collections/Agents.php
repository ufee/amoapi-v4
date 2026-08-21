<?php
/**
 * amoCRM API client Amma Agents Collection
 */
namespace Ufee\AmoV4\Collections;

class Agents extends Entities
{
	/**
	 * Save agents: batch create or one-by-one update
	 * @return bool
	 */
	public function save()
	{
		if (!$first = $this->first()) {
			return false;
		}
		if ($first->id) {
			foreach ($this->items as $item) {
				if (!$item->save()) {
					return false;
				}
			}
			return true;
		}
		return parent::save();
	}
}
