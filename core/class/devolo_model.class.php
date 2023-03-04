<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class devolo_model {
    private static function cast ($array, $code) {
        $model = new devolo_model();
        foreach ($array as $key => $value){
            $model->$key = $value;
        }
        $model->code = $code;
        if (isset ($model->image)) {
            $country = config::bykey('country','devolo_cpl','ch');
            if ($country == 'be') {
                $country = 'fr';
            }
            $imgDir = realpath( __DIR__ . '/../../desktop/img' ) . '/';
            $img = $country . '-' . $model->image;
            if ( file_exists($imgDir . $img)){
		$dir = preg_replace('/^.*?(\/plugins\/.*)/','\1',$imgDir);
                $model->image = $dir . $img;
            }
	} else {
	    $model->image = '/plugins/devolo_cpl/plugin_info/devolo_cpl_icon.png';
	}
        return $model;
    }

    public static function all() {
        $models = json_decode(file_get_contents(__DIR__ . "/../config/models.json"),true);
        $result = [];
        foreach (array_keys($models) as $code) {
            $result[] = self::cast($models[$code], $code);
        }
        return $result;
    }

    public static function byCode($code) {
        $models = json_decode(file_get_contents(__DIR__ . "/../config/models.json"),true);
        $model = null;
        if (isset ($models[$code])) {
            $model = self::cast($models[$code], $code);;
        }
        return $model;
    }

    public function getText() {
        return $this->texte;
    }

    public function getImage() {
        return $this->image;
    }

    public function getCplSpeed() {
        return $this->cpl_speed;
    }

    public function isManageable() {
	if ($this->manageable == 1) {
	    return true;
	}
        return false;
    }

    public function getManageable() {
        return $this->manageable;
    }

    public function getCode() {
        return $this->code;
    }
}
