<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class DateField extends EntityField
{
    /**
     * Get account timezone, amoCRM keeps date fields at account local midnight
     * @return \DateTimeZone
     */
    protected function timezone()
    {
        return $this->model->service->instance->timezone();
    }

    /**
     * Get date as DateTime
     * @return \DateTime|null
     */
    public function getDateTime()
    {
        if (is_null($value = $this->getValue())) {
            return null;
        }
        $date = new \DateTime('@' . (int) $value);
        $date->setTimezone($this->timezone());
        return $date;
    }

    /**
     * Set date value, calendar date is stored as account local midnight
     * @param string|int|\DateTimeInterface|null $value
     * @return EntityField
     */
    public function setDate($value)
    {
        if (is_null($value)) {
            return $this->reset();
        }
        if (is_int($value)) {
            return $this->setValue($value);
        }
        $date = $this->parseDate($value);
        return $this->setValue(
            (int) (new \DateTime($date->format('Y-m-d') . ' 00:00:00', $this->timezone()))->format('U')
        );
    }

    /**
     * Parse date value in account timezone
     * @param string|\DateTimeInterface $value
     * @return \DateTimeInterface
     * @throws \InvalidArgumentException
     */
    protected function parseDate($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Invalid date value type: ' . gettype($value));
        }
        try {
            return new \DateTime($value, $this->timezone());
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date value: ' . $value);
        }
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
