<?php
namespace app\components;
use \Firebase\JWT\JWT;


class JWTCom {
    static function createJWT($user)
    {
        include_once '..\config\core.php';
        include_once '..\libs\php-jwt-master\src\BeforeValidException.php';
        include_once '..\libs\php-jwt-master\src\ExpiredException.php';
        include_once '..\libs\php-jwt-master\src\SignatureInvalidException.php';
        include_once '..\libs\php-jwt-master\src\JWT.php';
        $token = array(
            "iss" => $iss,
            "aud" => $aud,
            "iat" => $iat,
            "nbf" => $nbf,
            "data" => array(
                "id" => $user->id,
                "email" => $user->email
            )
            );
        $jwt = JWT::encode($token, $key);
        return $jwt;
    }
    static function decodeJWT($jwt)
    {
        $result =[];
        include_once '../config/core.php';
        include_once '../libs/php-jwt-master/src/BeforeValidException.php';
        include_once '../libs/php-jwt-master/src/ExpiredException.php';
        include_once '../libs/php-jwt-master/src/SignatureInvalidException.php';
        include_once '../libs/php-jwt-master/src/JWT.php';
        try{
            $decoded = JWT::decode($jwt, $key, array('HS256'));
            $result['status']='success';
            $result['token'] = $decoded;
            Logger::getLogger("dev")->log("Успешное декодирование jwt");
        } catch (\Exception $e){

            $result['status']="fail";
            $result["error"] = $e->getMessage();
            Logger::getLogger("dev")->log($e);
        }
        return $result;        
    }

}