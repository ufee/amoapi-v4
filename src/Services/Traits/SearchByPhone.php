<?php
/**
 * amoCRM trait - search entitys by phone
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Collections\Entities;

trait SearchByPhone
{
    /**
     * Get entitys by phone
	 * @param string $phone
	 * @param int $page_limit
	 * @param array $with
	 * @param string $format
	 * @return Entities
     */
	public function searchByPhone(string $phone, int $page_limit = 0, array $with = [], string $format = self::PHONE_RU_MOB)
	{
		$method = 'searchBy_'.$format;
		if (!method_exists($this, $method)) {
			throw new \Exception('Invalid search format: '.(string)$format);
		}
		return call_user_func(
			[$this, $method], $phone, $page_limit, $with
		);
	}
	
    /**
     * Get by phone Ru Mobile
	 * @param string $phone
	 * @param int $page_limit
	 * @param array $with
	 * @return Entities
     */
	protected function searchBy_ru_mob(string $phone, int $page_limit = 0, array $with = [])
	{
		$clearPhone = function($phone) {
			return substr(preg_replace('#[^0-9]+#Uis', '', (string)$phone), -10);
		};
		$field_name = $this->instance->getParam('lang') == 'ru' ? 'Телефон' : 'Phone';
		$query = $clearPhone($phone);
		if (strlen($query) < 6) {
			throw new \Exception('Invalid search phone value: '.$phone);
		}
		$models = $this->search($query, $with)->pagesLimit($page_limit)->fetchAll();
		$matches = $this->createCollection();

		foreach ($models as $model) {
			$cf = $model->cf($field_name);
			$values = $model->cf($field_name)->getValues();
			foreach ($values as $value) {
				if ($query === $clearPhone($value)) {
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
