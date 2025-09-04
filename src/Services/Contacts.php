<?php
/**
 * amoCRM API client Contacts service
 */
namespace Ufee\AmoV4\Services;

class Contacts extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\SearchByEmail;
	use Traits\SearchByPhone;
	use Traits\Cfields;
	use Traits\Notes;
	
	const PHONE_RU_MOB = 'ru_mob';
	
	protected $api_path = '/api/v4/contacts';
	protected $entity_key = 'contacts';

	protected $entity_model = '\Ufee\AmoV4\Models\Contact';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Contacts';
}
