<?php
namespace app\controllers;
use app\controllers\ApiController;
use app\components\JWTCom;
use app\models\Users;
use yii;
use app\components\Logger;


class UserController extends ApiController
{
    public $enableCsrfValidation = false;
    
    /**
     * Возвращает JSON строку с сообщением о статусе операции либо сообщение об ошибке
     *
     * POST-параметры:
     *                JSON строка содержащая email и пароль пользователя для его создания
     */
    public function actionCreate()
    {
        $post=(file_get_contents("php://input"));
        $data = json_decode($post);
        $email = $data->email;
        $password   = $data->password;

        Logger::getLogger("dev")->log("Начата регистрация пользователя");
        if (!empty($email) && !empty($password))
        {
            if (Users::find()->where(['email'=>$email])->exists())
            {
                Logger::getLogger("dev")->log("Регистрация пользователя невозможна email занят");
                $this->responce(array(
                    "status" => "fail",
                    "error" => "Указанный email существует."),400);
            }

                $user=new Users();
                $user -> email=$email;
                $user -> password=password_hash($password,PASSWORD_BCRYPT);
                $user -> save();
                // устанавливаем код ответа 
                Logger::getLogger("dev")->log("Пользователь успешно зарегестрировался");
                $this->responce(array(
                    "status" => "success",
                    "message" => "Пользователь был создан."));

        }
        // сообщение, если не удаётся создать пользователя 
        Logger::getLogger("dev")->log("Нет данных для регистрации");
        $this->responce(array(
            "status" => "fail",
            "error" => "Невозможно создать пользователя."),400);
    }
    /**
     * Возвращает JSON строку с сообщением о статусе операции либо сообщение об ошибке
     *
     * POST-параметры:
     *                JSON строка содержащая email и пароль
     */
    public function actionLogin()
    {
        $post=(file_get_contents("php://input"));
        $data = json_decode($post);
        $email = $data->email;
        $password = $data->password;

        Logger::getLogger("dev")->log("Пользователь пытается войти");

        if (empty($email) || empty($password)){

            Logger::getLogger("dev")->log("Ошибка: пустые данные");
            $this->responce(array(
                "status" => "fail",
                "error"=>"Поля пароль или email не заполнены"),400);
        }
        if (Users::find()->where(['email'=>$email])->exists())
        {
            $user=Users::find()->where(['email'=>$email])->one();
            if (password_verify($password,$user->password)) {
             $jwt=JWTCom::createJWT($user);
             
             Logger::getLogger("dev")->log("Пользователь успешно вошел в систему");
                
             $this->responce(array(
                    "status" => "success",
                    "message" => "Успешный вход в систему.","jwt" => $jwt),200);
            }
            Logger::getLogger("dev")->log("пользователь ввел неверный пароль");

            $this->responce(array(
                "status" => "fail",
                "error"=>"Пароль неверный"),400);
        }

        Logger::getLogger("dev")->log("Указанного пользователя нет в бд");
        $this->responce(array(
            "status" => "fail",
            "error"=>"Пользователь с указанным email не существует"),400);
    }

    /**
     * Возвращает JSON строку с сообщением о статусе операции либо сообщение об ошибке
     *
     * POST-параметры:
     *                JSON строка содержащая email и  новый пароль для пользователя.
     */
    public function actionChange(){
        $post=(file_get_contents("php://input"));
        $data = json_decode($post);
        $email = $data->email;
        $newPassword = $data->newPassword;

        if (empty($email) || empty($newPassword)){
            $this->responce(array(
                "status" => "fail",
                "error"=>"Поле пароль или email пустое"),400);
        }
        if (Users::find()->where(['email'=>$email])->exists())
        {
            // Представим что-где-то тут отправка письма на мыло
            // Костыли
            $user=Users::find()->where(['email'=>$email])->one();
            $user ->password=password_hash($newPassword,PASSWORD_BCRYPT);
            $this->responce(array(
                "status" => "success",
                "message"=>"Пароль изменен"));

        }
        $this->responce(array(
           "status" => "fail",
            "error"=>"Пользователь с указанным email не существует"),400);              
    }   
}
?>
