<?php
namespace app\controllers;
use yii\web\Controller;
use yii;
use app\components\Logger;
class ApiController extends Controller
{

    function getStatusCodeMessage($status)
    {
        $codes  = Array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
    function responce($body = [],$status = 200,$content_type='application/json')
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status);
        header($status_header);
        header('Content-type: ' . $content_type);
        echo json_encode($body);
        exit();
    }

    private function ErrorResponce($body,$status=500)
    {
        Logger::getLogger("dev")->log("Что-то пошло не так:".$body["error"]);
        $this->responce($body,$status);
    }

    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if($exception->statusCode==404){
            $this->ErrorResponce(array("error"=>"Not found"),404);
        }
        if($exception->statusCode==500){
            $this->ErrorResponce(array("error"=>"Сервер болеет",500));
        }

    }
}