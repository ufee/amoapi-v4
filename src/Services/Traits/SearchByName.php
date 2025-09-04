<?php
/**
 * amoCRM trait - search entitys by name
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Exceptions;
use Ufee\AmoV4\Collections\Entities;

trait SearchByName
{
    /**
     * Get entitys by name
	 * @param string $name
	 * @param string $field
	 * @param int $page_limit
	 * @param array $with
	 * @return Entities
     */
	public function searchByName(string $name, int $page_limit = 0, array $with = [])
	{
		$clearName = function($name) {
			return mb_strtolower(trim((string)$name));
		};
		$query = $clearName($name);
		
		if (strlen($query) < 3) {
			throw new Exceptions\AmoException('Invalid search name: '.$query);
		}
		$models = $this->search($query, $with)->pagesLimit($page_limit)->fetchAll();
		$matches = $this->createCollection();

		foreach ($models as $model) {
			if ($query === $clearName($model->name)) {
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
