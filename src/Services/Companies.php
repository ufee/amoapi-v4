<?php
/**
 * amoCRM API client Companies service
 */
namespace Ufee\AmoV4\Services;

class Companies extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\SearchByEmail;
	use Traits\SearchByPhone;
	use Traits\Cfields;
	use Traits\Notes;
	
	const PHONE_RU_MOB = 'ru_mob';
	
	protected $api_path = '/api/v4/companies';
	protected $entity_key = 'companies';

	protected $entity_model = '\Ufee\AmoV4\Models\Company';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Companies';
}
