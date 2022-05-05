<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost:3307;dbname=users',
    'username' => 'root',
    'password' => 'newpassword',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    'schemaCacheDuration' => 0,
    //'schemaCache' => 'cache',
];
