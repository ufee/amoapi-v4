<?php
/**
 * amoCRM Model Trait — entity file links
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Collections;
use Ufee\AmoV4\Models\File;

trait Files
{
	/**
	 * Get files linked to entity
	 * @param array $args
	 * @return Collections\Collection
	 */
	public function getFiles(array $args = [])
	{
		return $this->service->instance->files()->getByEntity(
			$this->service->entity_key,
			$this->id,
			$args
		);
	}

	/**
	 * Attach file(s) to entity
	 * @param string|array|File $uuids
	 * @return bool
	 */
	public function attachFiles($uuids)
	{
		return $this->service->instance->files()->attachToEntity(
			$this->service->entity_key,
			$this->id,
			$this->normalizeFileUuids($uuids)
		);
	}

	/**
	 * Detach file(s) from entity
	 * @param string|array|File $uuids
	 * @return bool
	 */
	public function detachFiles($uuids)
	{
		return $this->service->instance->files()->detachFromEntity(
			$this->service->entity_key,
			$this->id,
			$this->normalizeFileUuids($uuids)
		);
	}

	/**
	 * @param string|array|File $uuids
	 * @return array
	 */
	protected function normalizeFileUuids($uuids)
	{
		if ($uuids instanceof File) {
			return [$uuids->uuid];
		}
		if (is_string($uuids)) {
			return [$uuids];
		}
		if (!is_array($uuids)) {
			throw new \InvalidArgumentException('File UUIDs must be string, File model or array');
		}
		$result = [];
		foreach ($uuids as $item) {
			if ($item instanceof File) {
				$result[] = $item->uuid;
			} else if (is_string($item)) {
				$result[] = $item;
			} else if (is_array($item) && !empty($item['file_uuid'])) {
				$result[] = $item['file_uuid'];
			} else if (is_array($item) && !empty($item['uuid'])) {
				$result[] = $item['uuid'];
			} else {
				throw new \InvalidArgumentException('Invalid file UUID item');
			}
		}
		return $result;
	}
}
