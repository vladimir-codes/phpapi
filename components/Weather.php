<?php

namespace app\components;

use Exception;
use Yii;

class Weather
{
    private static function getWeatherСoefficient($code)
    {
        $codes  = array(
            200 => 0.6,     //thunderstorm with light rain
            201 => 0.5,     //thunderstorm with rain
            202 => 0.4,     //thunderstorm with heavy rain
            210 => 0.5,     //light thunderstorm
            211 => 0.4,     //thunderstorm
            212 => 0.3,     //heavy thunderstorm
            221 => 0.3,     //ragged thunderstorm
            230 => 0.4,     //thunderstorm with light drizzle
            231 => 0.4,     //thunderstorm with drizzle
            232 => 0.3,     //thunderstorm with heavy drizzle

            300 => 0.8,     //light intensity drizzle
            301 => 0.7,     //drizzle
            302 => 0.6,     //heavy intensity drizzle
            310 => 0.6,     //light intensity drizzle rain
            311 => 0.5,     //drizzle rain
            312 => 0.4,     //heavy intensity drizzle rain
            313 => 0.4,     //shower rain and drizzle
            314 => 0.3,     //heavy shower rain and drizzle
            321 => 0.4,     //shower drizzle

            500 => 0.7,     //light rain
            501 => 0.6,     //moderate rain
            502 => 0.5,     //heavy intensity rain
            503 => 0.4,     //very heavy rain
            504 => 0.4,     //extreme rain
            511 => 0.3,     //freezing rain
            520 => 0.3,     //light intensity shower rain
            521 => 0.3,     //shower rain
            522 => 0.3,     //heavy intensity shower rain
            531 => 0.3,     //ragged shower rain

            600 => 0.7,     //light snow
            601 => 0.6,     //Snow
            602 => 0.4,     //Heavy snow
            611 => 0.4,     //Sleet
            612 => 0.4,     //Light shower sleet
            613 => 0.3,     //Shower sleet
            615 => 0.3,     //Light rain and snow
            616 => 0.2,     //Rain and snow
            620 => 0.3,     //Light shower snow
            621 => 0.2,     //Shower snow
            622 => 0.2,     //Heavy shower snow

            701 => 0.4,     //mist
            711 => 0.3,     //Smoke
            721 => 0.3,     //Haze
            731 => 0.3,     //sand/ dust whirls
            741 => 0.2,     //fog
            751 => 0.2,     //sand
            761 => 0.2,     //dust
            762 => 0.0,     //volcanic ash
            771 => 0.0,     //squalls
            781 => 0.0,     //tornado

            800 => 0.9,     //clear sky
            801 => 0.9,     //few clouds: 11-25%
            802 => 1.0,     //scattered clouds: 25-50%
            803 => 1.0,     //broken clouds: 51-84%
            804 => 0.9,     //overcast clouds: 85-100%
        );
        return (isset($codes[$code])) ? number_format($codes[$code],2) : 'error';
    }

    /**
     * Вычисление погоды по координатам
     * @param $lat
     * @param $lng
     * @return float|string|void
     */
    public static function getWeather($lat, $lng)             //Возвращает
    {
        try {
            $url = Yii::$app->params['weatherURL'];
            $options = array(
                'lat' => $lat,
                'lon' => $lng,
                'appid' => Yii::$app->params['weatherKey'],
                'units' => 'metric',
                'lang' => 'ru',
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($options));

            $responce = curl_exec($ch);

            curl_close($ch);
            $test =  json_decode($responce, true);
            return Weather::getWeatherСoefficient($test['weather']['0']['id']);
        } catch (Exception $e) {
            Logger::getLogger("dev")->log("Ошибка добавления погоды для $lat $lng $e->getMessage()");
        }
    }
}