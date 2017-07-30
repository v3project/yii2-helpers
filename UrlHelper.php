<?php
namespace v3project\helpers;

use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\base\InvalidParamException;


class UrlHelper {

    static public function get_parameters_from_url($url = null) {

        if (!isset($url)) $url = \Yii::$app->getRequest()->getAbsoluteUrl();
        if (!is_string($url)) throw new InvalidParamException('(!is_string($url))');

        $parsed_url = parse_url($url);
        parse_str($parsed_url['query'], $parsed_url_query);
        return $parsed_url_query;
    }
    static public function add_parameters_to_url($url = null, $pname2value = []) {

        if (!isset($url)) $url = \Yii::$app->getRequest()->getAbsoluteUrl();
        if (!is_string($url)) throw new InvalidParamException('(!is_string($url))');

        if (empty($pname2value)) return $url;
        if (!is_array($pname2value)) throw new InvalidParamException('(!is_array($pname2value))');

        $parsed_url = parse_url($url);

        if (empty($parsed_url['query'])) $parsed_url_query = [];
        else parse_str($parsed_url['query'], $parsed_url_query);

        foreach ($pname2value as $k => $v) {
            if (!isset($v)) unset($parsed_url_query[$k]);
            else $parsed_url_query[$k] = $v;
        }

        $parsed_url['query'] = self::build_query($parsed_url_query);

        $url = self::build_url($parsed_url);

        return $url;
    }
    static public function remove_parameters_from_url($url = null, $pnames = []) {

        if (!isset($url)) $url = \Yii::$app->getRequest()->getAbsoluteUrl();
        if (!is_string($url)) throw new InvalidParamException('(!is_string($url))');

        if (empty($pnames)) return $url;
        if (!is_array($pnames)) throw new InvalidParamException('(!is_array($pnames))');

        $pname2value = array_fill_keys($pnames, null);

        return static::add_parameters_to_url($url, $pname2value);
    }

    static public function build_query($data, $glue = null, $use_rawurlencode = false) {

        if (!is_array($data)) throw new InvalidParamException('(!is_array($data))');

        if (!isset($glue)) $glue = '&';
        if (!is_string($glue)) throw new InvalidParamException('(!is_string($glue))');

        if (!is_bool($use_rawurlencode)) throw new InvalidParamException('(!is_array($use_rawurlencode))');

        $ret = [];
        foreach ($data as $k => $v) {
            if (!isset($v)) continue;
            elseif ($use_rawurlencode) $ret[] = rawurlencode($k).'='.rawurlencode($v);
            else $ret[] = urlencode($k).'='.urlencode($v);
        }
        return implode($glue, $ret);
    }
    static public function build_url($parsed_url) {

        if (!is_array($parsed_url)) throw new InvalidParamException('(!is_array($parsed_url))');

        $href = '';
        if (!empty($parsed_url['host'])) {
            if (!empty($parsed_url['scheme'])) $href .= $parsed_url['scheme'].'://';
            if (!empty($parsed_url['user'])) {
                $href .= $parsed_url['user'];
                if (!empty($parsed_url['pass'])) $href .= ':'.$parsed_url['pass'];
                $href .= '@';
            }
            $href .= $parsed_url['host'];
            if (!empty($parsed_url['port'])) $href .= ':'.$parsed_url['port'];
        }

        if (empty($parsed_url['path']) AND (!empty($parsed_url['query']) OR !empty($parsed_url['fragment']))) $parsed_url['path'] = '/';
        if (!empty($parsed_url['path']) AND ($parsed_url['path'] === '/') AND empty($parsed_url['query']) AND empty($parsed_url['fragment'])) $parsed_url['path'] = '';

        if (!empty($parsed_url['path'])) $href .= $parsed_url['path'];
        if (!empty($parsed_url['query'])) $href .= '?'.$parsed_url['query'];
        if (!empty($parsed_url['fragment'])) $href .= '#'.$parsed_url['fragment'];

        return $href;
    }
    static public function update_url($url = null, $for_update = []) {

        if (!isset($url)) $url = \Yii::$app->getRequest()->getAbsoluteUrl();
        if (!is_string($url)) throw new InvalidParamException('(!is_string($url))');

        if (empty($for_update)) $for_update = [];
        if (!is_array($for_update)) throw new InvalidParamException('(!is_array($for_update))');

        $parsed_url = parse_url($url);

        foreach ($for_update as $kkk => $vvv) {
            if ($kkk === 'params') $kkk = 'query';
            if (($kkk === 'query') AND is_array($vvv)) {

                $parsed_url_query = [];
                if (!empty($parsed_url['query'])) parse_str($parsed_url['query'], $parsed_url_query);

                foreach ($vvv as $qk => $qv) {
                    if (!isset($qv)) unset($parsed_url_query[$qk]);
                    else $parsed_url_query[$qk] = $qv;
                }

                $parsed_url['query'] = self::build_query($parsed_url_query);

                continue;
            }
            $parsed_url[$kkk] = $vvv;
        }

        $url = self::build_url($parsed_url);

        return $url;
    }





}