<?php

namespace app\controllers;

use Exception;
use yii\web\Controller;
use Yii;
use app\components\JWTCom;
use app\components\Logger;
use app\components\Weather;

class RouteController extends ApiController
{
    public $enableCsrfValidation = false;

    /**
     * Возвращает готовый маршрут с углами поворотов и погодой для каждой точки маршрута
     *
     * GET-параметры:
     *                key-Ключ API
     *                from Координаты|Адрес начала маршрута
     *                to Координаты|Адрес начала маршрута
     *                onlyTurns Если true, то АПИ вернет только все точки маршрута с погодой и кглами,иначе вернет весь исходный массив
     */
    public function actionGetRoute()
    {
        $request = Yii::$app->request;

        if ($request->get('key') != null) {
            $result = JWTCom::decodeJWT($request->get('key'));
            if ($result["status"] == "success") {
                Logger::getLogger("dev")->log("Начато получение API маршрута");
                if (($request->get('from') != null && $request->get('to')!=null)) {
                    $data = $this->getFullApi($request->get('from'),$request->get('to'));
                    $turns = $this->getTurns($data);
                    $this->InsertAngleIntoArr($turns);
                    $this->InsertWeatherIntoArr($turns);
                    $this->addDirection($turns,$data);

                    if ($request->get('onlyTurns') == 'true') $data = $turns;
                    Logger::getLogger("dev")->log("Успешно получен маршрут по ключу: " . $request->get('key'));

                    $this->responce(array(
                        "status" => "success",
                        "route" => ($data)
                    ), 200);
                }
                Logger::getLogger("dev")->log("Введен не полный запрос");
                $this->responce(array(
                    "status" => "fail",
                    "message" => "Ошибка запроса(недостаточно данных).",
                ), 400);
            }
            Logger::getLogger("dev")->log("Что-то пошло не так");
            $this->responce(array(
                "status" => "fail",
                "message" => "Доступ закрыт.",
                "error" => $result['error']
            ), 400);
        }

        Logger::getLogger("dev")->log("Пользователь не авторизован");
        $this->responce(array(
            "status" => "fail",
            "message" => "Доступ запрещён."
        ), 401);
    }

    /**
     * Выбирает все точки маршрута и возвращает их в виде ссылочного массива
     * @param $data - Исходный(полный) массив ,полученный по АПИ
     * @return array|null
     */
    private function getTurns(&$data)
    {
        $turns = [];
        try {
            foreach ($data['route']['legs']['0']['maneuvers'] as &$startpoint) {
                $turns[] = &$startpoint['startPoint'];
            }
            Logger::getLogger("dev")->log("Успешно получены все точки маршрута");
            return $turns;
        } catch (Exception $e) {
            Logger::getLogger("dev")->log("Ошибка поиска маршрута" . $e->getMessage());
            $this->responce(array(
                "status" => "fail",
                "message" => "Маршрут не найден" . $e->getMessage(),
            ), 400);
        }
        return null;
    }

    private function addDirection(&$array,&$data)
    {
        for ($i = 0; $i <= count($array) - 1; $i++) {
            $array[$i]['turnType'] = $this->getNameTypeTurn($data['route']['legs']['0']['maneuvers'][$i]['turnType']);
        }
    }

    private function getNameTypeTurn($code)
    {
        $turntypes= array(
        
            0 =>  'Прямо',
            1 =>  "Немного вправо",
            2 =>  "Направо",
            3 =>  "Резко вправо",
            4 =>  "Назад",
            5 =>  "Резко налево",
            6 =>  "Налево",
            7 =>  "Немного налево",
            8 =>  "Разворот направо",
            9 =>  "Разворот налево",
            10 =>  "Правое слияние с другой дорогой",
            11 =>  "Левое слияние с другой дорогой",
            12 =>  "Направо на пандус",
            13 =>  "Налево на пандус",
            14 =>  "Направо с пандуса",
            15 =>  "Налево с пандуса",
            16 =>  "Направо на ответвлении",
            17 =>  "Налево на ответвлении",
            18 =>  "Прямо на ответвлении",
        );
        return (isset($turntypes[$code])) ? $turntypes[$code] : 'error'; 
    }

    /**
     * Добавляет погоду для каждой точки маршрута в исходный(полный) массив
     * @param $array -  Массив всех точек маршрута
     */
    private function InsertWeatherIntoArr(&$array)
    {
        for ($i = 0; $i <= count($array) - 1; $i++) {
            $array[$i]['weather'] = Weather::getWeather($array[$i]['lat'], $array[$i]['lng']);
        }
    }

    /**
     * Добавляет углы поворота в исходный(полный) массив
     * @param $array - Массив всех точек маршрута
     */
    private function InsertAngleIntoArr(&$array)
    {

        for ($i = 1; $i <= count($array) - 2; $i++) {
            $array[$i]['angle'] = $this->CalculateAngleByVectors($array[$i - 1], $array[$i], $array[$i + 1]);
        }
    }

    /**
     * Вычисляет угол между 3 точками (Выпускает 2 вектора из центральной точки и находит угол между ними)
     * @param $prev -Координаты первой точки
     * @param $current -Координаты центральной точки
     * @param $next -Координаты последней точки
     * @return float|string
     */
    private function CalculateAngleByVectors($prev, $current, $next)
    {
        try {
            $vectorX = [
                $current['lat'] - $prev['lat'],
                $current['lng'] - $prev['lng']
            ];
            $vectorY = [
                $current['lat'] - $next['lat'],
                $current['lng'] - $next['lng']
            ];
            $scalarMul = $vectorX[0] * $vectorY[0] + $vectorX[1] * $vectorY[1];         //Скалярное произведение векторов
            $LengthX = sqrt(pow($vectorX[0], 2) + pow($vectorX[1], 2)); //Длина первого вектора
            $LengthY = sqrt(pow($vectorY[0], 2) + pow($vectorY[1], 2)); //Длина второго вектора
            return number_format(acos(($scalarMul / ($LengthX * $LengthY))) * 180 / pi(), 2);
        } catch (Exception $e) {
            Logger::getLogger("dev")->log("Ошибка вычисления угла поворота $e->getMessage()\n" . json_encode($prev) . "\n" . json_encode($current) . "\n" . json_encode($next));
        }
        return "0";
    }


    /**
     * Получение полного массива со всеми данными по маршруту по АПИ
     * @param $from -Координаты|Адрес начала маршрута
     * @param $to -Координаты|Адрес окончания маршрута
     * @return mixed
     */
    private function getFullApi($from ,$to)
    {
        Logger::getLogger("dev")->log("Получение маршрута с MapQuest");

        $curl = curl_init();
        curl_setopt_array($curl,[
            CURLOPT_URL => Yii::$app->params['routeURL']."?key=".Yii::$app->params['routeKey']."&from=$from&to=$to&locale=ru_RU",
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $responce = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($responce,true);
        return $data;
    


    }
}
