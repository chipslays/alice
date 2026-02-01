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
        $val = $this->data['value'] ?? [];
        if (!is_array($val)) {
            return null;
        }

        if (!empty($val['year_is_relative'])) {
            return date('Y', (int) strtotime((string) ($val['year'] ?? '0') . ' year'));
        }

        return is_scalar($val['year'] ?? null) ? (string) $val['year'] : null;
    }

    /**
     * Возвращает месяц из сущности даты (1-12) или null.
     *
     * @return string|null
     */
    public function month(): ?string
    {
        $val = $this->data['value'] ?? [];
        if (!is_array($val)) {
            return null;
        }

        if (!empty($val['month_is_relative'])) {
            return date('n', (int) strtotime((string) ($val['month'] ?? '0') . ' month'));
        }

        return is_scalar($val['month'] ?? null) ? (string) $val['month'] : null;
    }

    /**
     * Возвращает день месяца из сущности даты или null.
     *
     * @return string|null
     */
    public function day(): ?string
    {
        $val = $this->data['value'] ?? [];
        if (!is_array($val)) {
            return null;
        }

        if (!empty($val['day_is_relative'])) {
            return date('j', (int) strtotime((string) ($val['day'] ?? '0') . ' day'));
        }

        return is_scalar($val['day'] ?? null) ? (string) $val['day'] : null;
    }

    /**
     * Возвращает час из сущности даты (0-23) или null.
     *
     * @return string|null
     */
    public function hour(): ?string
    {
        $val = $this->data['value'] ?? [];
        if (!is_array($val)) {
            return null;
        }

        if (!empty($val['hour_is_relative'])) {
            return date('G', (int) strtotime((string) ($val['hour'] ?? '0') . ' hour'));
        }

        return is_scalar($val['hour'] ?? null) ? (string) $val['hour'] : null;
    }

    /**
     * Возвращает минуту из сущности даты или null.
     *
     * @return string|null
     */
    public function minute(): ?string
    {
        $val = $this->data['value'] ?? [];
        if (!is_array($val)) {
            return null;
        }

        if (!empty($val['minute_is_relative'])) {
            return (string) intval(date('i', (int) strtotime((string) ($val['minute'] ?? '0') . ' minute')));
        }

        return is_scalar($val['minute'] ?? null) ? (string) $val['minute'] : null;
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

        $tz = null;
        if ($timezone) {
            $tz = new DateTimeZone($timezone);
        }

        return new DateTime($dateStr, $tz);
    }
}