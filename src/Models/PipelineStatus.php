<?php
/**
 * amoCRM PipelineStatus model
 */
namespace Ufee\AmoV4\Models;

class PipelineStatus extends Model
{
	const STATUS_WON = 142;
	const STATUS_LOST = 143;

	/**
	 * Get pipeline
	 * @return Pipeline
	 */
	public function pipeline(): Pipeline
	{
		return $this->service->instance->cache->pipeline($this->pipeline_id);
	}

	/**
	 * Get next pipeline status
	 * @return PipelineStatus|null
	 */
	public function next(): ?PipelineStatus
	{
		$pipeline = $this->service->instance->cache->pipeline($this->pipeline_id);

		$status = $pipeline->statuses()
			->find(function ($status) {
				return $status->id != self::STATUS_LOST && $status->sort > $this->sort;
			})
			->first();

		return $status ? $status : null;
	}

	/**
	 * Get previous pipeline status
	 * @return PipelineStatus|null
	 */
	public function previous(): ?PipelineStatus
	{
		$pipeline = $this->service->instance->cache->pipeline($this->pipeline_id);

		$status = $pipeline->statuses()
			->find(function ($status) {
				return !in_array($status->id, [self::STATUS_WON, self::STATUS_LOST]) && $status->sort < $this->sort;
			})
			->last();

		return $status ? $status : null;
	}

	/**
	 * Delete current
	 * @return bool
	 */
	public function delete()
	{
		return $this->service->delete($this->id);
	}
}
