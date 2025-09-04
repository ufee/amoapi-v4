<?php
/**
 * amoCRM trait - search entitys by custom field
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Exceptions;
use Ufee\AmoV4\Collections\Entities;

trait SearchByCustomField
{
    /**
     * Get entitys by cf
	 * @param string $query
	 * @param string $field name
	 * @param int $page_limit
	 * @param array $with
	 * @return Entities
     */
	public function searchByCustomField(string $query, string $field, int $page_limit = 0, array $with = [])
	{
		$query = trim($query);
		if (strlen($query) < 3) {
			throw new Exceptions\AmoException('Invalid cf query filter value: '.$query);
		}
		$models = $this->search($query, $with)->pagesLimit($page_limit)->fetchAll();
		$matches = $this->createCollection();

		foreach ($models as $model) {
			if (!$cf = $model->cf($field)) {
				continue;
			}
			$value = trim((string)$cf->getValue());
			if ($query === $value) {
				$matches->push($model);
			}
			$model = null;
			unset($model);
		}
		$models = null;
		unset($models);
		
		return $matches;
	}
}
