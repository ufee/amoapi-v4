<?php
/**
 * amoCRM API client Widgets service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Models;
use Ufee\AmoV4\Exceptions;

class Widgets extends Service
{
	protected $api_path = '/api/v4/widgets';
	protected $entity_key = 'widgets';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Widget';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Widgets';

    /**
     * Install widget
	 * @param string $widget_code
	 * @param array $settings
	 * @return Models\Widget
     */
	public function install(string $widget_code, array $settings)
	{
		$query = $this->instance->query('POST', $this->api_path.'/'.$widget_code);
		$query->setJsonData($settings);
		$query->execute();
		
		$result = $query->response->validated();
		return new Models\Widget((array)$result, $this);
	}
	
    /**
     * Uninstall widget
	 * @param string $widget_code
	 * @return bool
     */
    public function uninstall(string $widget_code)
    {
		$query = $this->instance->query('DELETE', $this->api_path.'/'.$widget_code);
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
