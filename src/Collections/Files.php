<?php
/**
 * amoCRM Files collection
 */
namespace Ufee\AmoV4\Collections;

class Files extends Entities
{
	/**
	 * Mass save is not supported for Drive files
	 * @return bool
	 */
	public function save()
	{
		throw new \BadMethodCallException('Use File::save() for updates or files()->upload() for create');
	}

	/**
	 * Delete all files in collection
	 * @return bool
	 */
	public function delete()
	{
		if (!$first = $this->first()) {
			return false;
		}
		$uuids = $this->collectUuids();
		if (empty($uuids)) {
			return false;
		}
		return $first->service->delete($uuids);
	}

	/**
	 * Restore all files in collection
	 * @return bool|Files
	 */
	public function restore()
	{
		if (!$first = $this->first()) {
			return false;
		}
		$uuids = $this->collectUuids();
		if (empty($uuids)) {
			return false;
		}
		return $first->service->restore($uuids);
	}

	/**
	 * Collect file UUIDs from models
	 * @return array
	 */
	protected function collectUuids()
	{
		$uuids = [];
		foreach ($this->items as $file) {
			if (!empty($file->uuid)) {
				$uuids[] = $file->uuid;
			}
		}
		return $uuids;
	}
}
