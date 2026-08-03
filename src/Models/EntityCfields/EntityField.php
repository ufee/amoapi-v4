<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;
use \Ufee\AmoV4\Models\WithCfield;

class EntityField
{
	/** Текст */
	public const TYPE_TEXT = 'text';
	/** Число */
	public const TYPE_NUMERIC = 'numeric';
	/** Флаг */
	public const TYPE_CHECKBOX = 'checkbox';
	/** Список */
	public const TYPE_SELECT = 'select';
	/** Мультисписок */
	public const TYPE_MULTISELECT = 'multiselect';
	/** Дата */
	public const TYPE_DATE = 'date';
	/** Ссылка */
	public const TYPE_URL = 'url';
	public const TYPE_MULTITEXT = 'multitext';
	/** Текстовая область */
	public const TYPE_TEXTAREA = 'textarea';
	/** Переключатель */
	public const TYPE_RADIOBUTTON = 'radiobutton';
	/** Короткий адрес */
	public const TYPE_STREETADDRESS = 'streetaddress';
	/** Адрес */
	public const TYPE_SMART_ADDRESS = 'smart_address';
	/** День рождения */
	public const TYPE_BIRTHDAY = 'birthday';
	/** Юр. лицо */
	public const TYPE_LEGAL_ENTITY = 'legal_entity';
	/** Предметы */
	public const TYPE_ITEMS = 'items';
	public const TYPE_ORG_LEGAL_NAME = 'org_legal_name';
	/** Категория */
	public const TYPE_CATEGORY = 'category';
	/** Дата и время */
	public const TYPE_DATE_TIME = 'date_time';
	/** Цена */
	public const TYPE_PRICE = 'price';
	/** Отслеживаемые данные */
	public const TYPE_TRACKING_DATA = 'tracking_data';
	/** Связь с другим элементом */
	public const TYPE_LINKED_ENTITY = 'linked_entity';
	/** Денежное (платная опция Супер-поля) */
	public const TYPE_MONETARY = 'monetary';
	/** Каталоги и списки (платная опция Супер-поля) */
	public const TYPE_CHAINED_LIST = 'chained_list';
	/** Файл */
	public const TYPE_FILE = 'file';
	/** Плательщик (только в списке Счета-покупки) */
	public const TYPE_PAYER = 'payer';
	/** Поставщик (только в списке Счета-покупки) */
	public const TYPE_SUPPLIER = 'supplier';

	protected $data;
	protected $model;

    /**
     * Constructor
	 * @param object $data
     */
    public function __construct(object $data, WithCfield $model)
    {
        $this->data = $data;
        $this->model = $model;
	}

    /**
     * Get cf value
	 * @return mixed
     */
    public function getValue()
    {
		if (!isset($this->data->values[0])) {
			return null;
		}
		return $this->data->values[0]->value ?? $this->data->values[0];
	}

    /**
     * Get cf values
	 * @return array
     */
    public function getValues()
    {
        $values = [];
		foreach ($this->data->values as $setted) {
			if (property_exists($setted, 'value')) {
				$values[]= $setted->value;
			}
        }
        return $values;
    }

    /**
     * Set cf value
	 * @param mixed $value value
	 * @return EntityField
     */
    public function setValue($value)
    {
		$this->data->values = [
			(object)['value' => $value]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Set cf values
	 * @param array $values
	 * @return EntityField
     */
    public function setValues(array $values)
    {
		$this->data->values = [];
		foreach($values as $value) {
			$this->data->values[]= (object)['value' => $value];
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Get cf enum
	 * @return integer|null
     */
    public function getEnum()
    {
		if (!isset($this->data->values[0])) {
			return null;
        }
		return $this->data->values[0]->enum_id;
    }

    /**
     * Set cf enum
	 * @param int $enum_id
	 * @return EntityField
     */
    public function setEnum(int $enum_id)
    {
		$this->data->values = [
			(object)['enum_id' => $enum_id]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Get cf enums
	 * @return array
     */
    public function getEnums()
    {
        $enums = [];
		foreach ($this->data->values as $setted) {
            $enums[]= $setted->enum_id;
        }
        return $enums;
    }

    /**
     * Set cf enums
	 * @param array $enum_ids
	 * @return EntityField
     */
    public function setEnums(array $enum_ids)
    {
		$this->data->values = [];
		foreach($enum_ids as $enum_id) {
			$this->data->values[]= (object)['enum_id' => $enum_id];
		}
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Reset cf value
	 * @return EntityField
     */
    public function reset()
    {
		$this->data->values = [];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

    /**
     * Get cf raw data
	 * @return object
     */
    public function getRawData()
    {
		return (object)[
			'field_id' => $this->data->field_id,
			'values' => $this->data->values
		];
	}

    /**
     * Protect get cf property
	 * @param string $property
     */
	public function __get(string $property)
	{
		if (property_exists($this->data, $property)) {
			return $this->data->{$property};
		}
		if ($property === 'field') {
			$service = $this->model->service;
			return $service->instance->cache->customFields(...$service->customFieldsArgs())->find('id', $this->data->field_id)->first();
		}
		return null;
	}
}
