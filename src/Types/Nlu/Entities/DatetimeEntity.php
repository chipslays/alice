<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Render;
use DateTime;
use DateTimeZone;

class DatetimeEntity extends Entity
{
    /**
     * Возвращает год из сущности даты (или null).
     *
     * @return string|null
     */
    public function year(): ?string
    {
        if (
            isset($this->data['value']['year_is_relative']) &&
            $this->data['value']['year_is_relative']
        ) {
            return date('Y', strtotime($this->data['value']['year'] . ' year'));
        }

        return $this->data['value']['year'] ?? null;
    }

    /**
     * Возвращает месяц из сущности даты (1-12) или null.
     *
     * @return string|null
     */
    public function month(): ?string
    {
        if (
            isset($this->data['value']['month_is_relative']) &&
            $this->data['value']['month_is_relative']
        ) {
            return date('n', strtotime($this->data['value']['month'] . ' month'));
        }

        return $this->data['value']['month'] ?? null;
    }

    /**
     * Возвращает день месяца из сущности даты или null.
     *
     * @return string|null
     */
    public function day(): ?string
    {
        if (
            isset($this->data['value']['day_is_relative']) &&
            $this->data['value']['day_is_relative']
        ) {
            return date('j', strtotime($this->data['value']['day'] . ' day'));
        }

        return $this->data['value']['day'] ?? null;
    }

    /**
     * Возвращает час из сущности даты (0-23) или null.
     *
     * @return string|null
     */
    public function hour(): ?string
    {
        if (
            isset($this->data['value']['hour_is_relative']) &&
            $this->data['value']['hour_is_relative']
        ) {
            return date('G', strtotime($this->data['value']['hour'] . ' hour'));
        }

        return $this->data['value']['hour'] ?? null;
    }

    /**
     * Возвращает минуту из сущности даты или null.
     *
     * @return string|null
     */
    public function minute(): ?string
    {
        if (
            isset($this->data['value']['minute_is_relative']) &&
            $this->data['value']['minute_is_relative']
        ) {
            return intval(date('i', strtotime($this->data['value']['minute'] . ' minute')));
        }

        return $this->data['value']['minute'] ?? null;
    }

    /**
     * Преобразует сущность в объект DateTime, используя доступные поля и переданные
     * значения по умолчанию.
     *
     * @param string|null $year Год
     * @param string|null $month Месяц
     * @param string|null $day День
     * @param string|null $hour Час
     * @param string|null $minute Минута
     * @param string|null $timezone Таймзона
     * @return DateTime
     */
    public function toDateTime(
        ?string $year = null,
        ?string $month = null,
        ?string $day = null,
        ?string $hour = null,
        ?string $minute = null,
        ?string $timezone = null,
    ): DateTime {
        $date = implode('-', [
            $this->year() ?? $year ?? date('Y'),
            $this->month() ?? $month ?? 1,
            $this->day() ?? $day ?? 1
        ]);

        $time = implode(':', [
            $this->hour() ?? $hour ?? 0,
            $this->minute() ?? $minute ?? 0
        ]);

        $dateStr = Render::finalize($date . ' ' . $time, 'text');

        if ($timezone) {
            $timezone = new DateTimeZone($timezone);
        }

        return new DateTime($dateStr, $timezone ?? null);
    }
}