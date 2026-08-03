<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class DateField extends EntityField
{
    /**
     * Get date as DateTime
     * @return \DateTime|null
     */
    public function getDateTime()
    {
        if (is_null($value = $this->getValue())) {
            return null;
        }
        $timezone = $this->model->service->instance->getParam('timezone');
        $date = new \DateTime('@' . (int) $value);
        $date->setTimezone(new \DateTimeZone($timezone));
        return $date;
    }

    /**
     * Get formatted date
     * @param string $format
     * @return string|null
     */
    public function format(string $format = 'Y-m-d')
    {
        if (!$date = $this->getDateTime()) {
            return null;
        }
        return $date->format($format);
    }
}
