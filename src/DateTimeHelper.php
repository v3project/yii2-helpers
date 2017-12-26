<?php
namespace v3project\helpers;;


use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class DateTimeHelper {


    static public function get_duts_by_postgres_ts($postgres_ts) {

        $ret = self::create_dt_from_postgres_ts($postgres_ts);
        $ret->setTime(0,0,0);
        $ret = $ret->getTimestamp();

        return $ret;
    }

    static public function get_postgres_ts_format($mode=null) {
        if ($mode === 'date') $format = 'Y-m-d';
        elseif ($mode === 'time') $format = 'H:i:sO';
        else $format = 'Y-m-d H:i:sO';
        return $format;
    }

    static public function format_dt_as_uts(\DateTimeInterface $dt = null) {
        if (!isset($dt)) return null;
        return $dt->getTimestamp();
    }
    static public function format_dt_as_duts(\DateTimeInterface $dt = null) {
        if (!isset($dt)) return null;
        if ($dt instanceof \DateTime) $dt = \DateTimeImmutable::createFromMutable($dt);
        /** @var \DateTimeImmutable $dt */
        return $dt->setTime(0,0,0)->getTimestamp();
    }
    static public function format_dt_as_postgres_ts(\DateTimeInterface $dt = null) {
        if (!isset($dt)) return null;
        $format = self::get_postgres_ts_format('datetime');
        $ret = $dt->format($format);
        if ((substr($format,-1) === 'O') AND (substr($ret,-2) === '00')) $ret = substr($ret,0,-2);
        return $ret;
    }
    static public function format_dt_as_postgres_date(\DateTimeInterface $dt = null) {
        if (!isset($dt)) return null;
        if ($dt instanceof \DateTime) $dt = \DateTimeImmutable::createFromMutable($dt);
        /** @var \DateTimeImmutable $dt */
        $dt->setTime(0,0,0);
        return $dt->format(self::get_postgres_ts_format('date'));
    }



    static public function create_dt_from_uts($uts, $deprecated_unused_parameter = null) {
        return static::create_dt($uts, 'uts');
    }
    static public function create_ddt_from_uts($uts, $deprecated_unused_parameter = null) {
        return static::create_ddt($uts, 'uts');
    }
    static public function create_dt_from_postgres_ts($value, $deprecated_unused_parameter = null) {
        return self::create_dt($value, [self::get_postgres_ts_format('timestamp'), 'Y-m-d H:i:sO', 'Y-m-d H:i:s.uO', 'Y-m-d\TH:i:sO']);
    }
    static public function create_ddt_from_postgres_date($value, $deprecated_unused_parameter = null) {
        return self::create_ddt($value, self::get_postgres_ts_format('date'));
    }

    /**
     * @return \DateTimeImmutable|null
     * @throws Exception
     */
    static protected function internal_create_dt($value, $formates = null, $throw = true) {
        if (!isset($value)) return null;

        if (empty($value)) {
            if ($throw) throw new Exception('Не удалось считать дату из пустой строки "'.$value.'"');
            else return null;
        }


        if (!isset($formates)) {
            $formates = [
                \DateTime::ISO8601, // 'Y-m-d\TH:i:sO';
                'Y-m-d H:i:sO',
                'Y-m-d H:i:s.uO',
                \DateTime::ATOM, // 'Y-m-d\TH:i:sP';
                'Y-m-d H:i:sP',
                'Y-m-d H:i:s.uP',
                'Y-m-d H:i:s',
                'Y-m-d H:i:s.u',
                'd.m.Y H:i:sO',
                'd.m.Y H:i:sP',
                'd.m.Y H:i:s',
                'd.m.y H:i:s',
                'd/m/Y H:i:s',
                'd/m/y H:i:s',
                'localized',
            ];
        }

        $formates = ArrayHelper::toArray($formates);
        foreach ($formates as $format) {
            $dt = null;
            if ($format === 'localized') {
                $matches = [];
                if (preg_match('/(\d{1,2})\s+(?:([А-Яа-я]+)|(\d{1,2}))\s+(\d{4})/iu', $value, $matches)) {
                    if (empty($matches[3]) and !empty($matches[2])) {
                        if (mb_stripos($matches[2], 'янв') !== false) $matches[2] = 1;
                        elseif (mb_stripos($matches[2], 'фев') !== false) $matches[2] = 2;
                        elseif (mb_stripos($matches[2], 'мар') !== false) $matches[2] = 3;
                        elseif (mb_stripos($matches[2], 'апр') !== false) $matches[2] = 4;
                        elseif (mb_stripos($matches[2], 'мая') !== false) $matches[2] = 5;
                        elseif (mb_stripos($matches[2], 'июн') !== false) $matches[2] = 6;
                        elseif (mb_stripos($matches[2], 'июл') !== false) $matches[2] = 7;
                        elseif (mb_stripos($matches[2], 'авг') !== false) $matches[2] = 8;
                        elseif (mb_stripos($matches[2], 'сен') !== false) $matches[2] = 9;
                        elseif (mb_stripos($matches[2], 'окт') !== false) $matches[2] = 10;
                        elseif (mb_stripos($matches[2], 'ноя') !== false) $matches[2] = 11;
                        elseif (mb_stripos($matches[2], 'дек') !== false) $matches[2] = 12;
                    }
                    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $matches[4].'-'.$matches[2].'-'.$matches[1]);
                    if (!empty($dt)) $dt = $dt->setTime(0,0,0);
                }
            }
            elseif ($format === 'uts') {
                $dt = new \DateTimeImmutable();
                $dt = $dt->setTimestamp(intval($value));
            }
            else {
                $dt = \DateTimeImmutable::createFromFormat($format, $value);
            }
            if (!empty($dt)) break;
        }

        if (!empty($dt)) {
            return $dt;
        }
        else {
            if ($throw) {
                throw new Exception('Не удалось считать дату из строки "'.$value.'", $formates=["'.implode('","',$formates).'"] $debug_backtrace='.VarDumper::dumpAsString(debug_backtrace()));
            }
            else {
                return null;
            }
        }
    }
    static public function create_dt($value, $formates = null, $deprecated_unused_parameter = null) {
        if (!isset($value)) return null;
        return static::internal_create_dt($value, $formates, true);
    }
    static public function create_dt_or_null($value, $formates = null) {
        if (!isset($value)) return null;
        return static::internal_create_dt($value, $formates, false);
    }

    /**
     * @return \DateTimeImmutable|null
     * @throws Exception
     */
    static public function internal_create_ddt($value, $formates = null, $throw = true) {
        if (!isset($value)) return null;
        if (!isset($formates)) {
            $formates = [
                'Y-m-d',
                'd.m.Y',
                'd.m.y',
                'd/m/Y',
                'd/m/y',
                'localized',
            ];
        }

        $ret = static::internal_create_dt($value, $formates, $throw);
        if (!empty($ret)) $ret = $ret->setTime(0,0,0);
        return $ret;
    }
    static public function create_ddt($value, $formates = null, $deprecated_unused_parameter = null) {
        if (!isset($value)) return null;
        return static::internal_create_ddt($value, $formates, true);
    }
    static public function create_ddt_or_null($value, $formates = null) {
        if (!isset($value)) return null;
        return static::internal_create_ddt($value, $formates, false);
    }


}