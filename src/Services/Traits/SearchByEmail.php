<?php
/**
 * amoCRM trait - search entitys by email
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Exceptions;
use Ufee\AmoV4\Collections\Entities;

trait SearchByEmail
{
    /**
     * Get entitys by email
	 * @param string $email
	 * @param int $page_limit
	 * @param array $with
	 * @return Entities
     */
	public function searchByEmail(string $email, int $page_limit = 0, array $with = [])
	{
		$clearEmail = function($email) {
			return mb_strtolower(trim((string)$email));
		};
		$query = $clearEmail($email);
		
		if (strlen($query) < 6 || !strpos($query, '@')) {
			throw new Exceptions\AmoException('Invalid search email value: '.$email);
		}
		$field_name = 'Email';
		$models = $this->search($query, $with)->maxPages($page_limit)->fetchAll();
		$matches = $this->createCollection();

		foreach ($models as $model) {
			if (!$cf = $model->cf($field_name)) {
				continue;
			}
			$values = $model->cf($field_name)->getValues();
			foreach ($values as $value) {
				if ($query === $clearEmail($value)) {
					$matches->push($model);
				}
			}
			$model = null;
			unset($model);
		}
		$models = null;
		unset($models);
		
		return $matches;
	}
}
