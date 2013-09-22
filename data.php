<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vladislav
 * Date: 28.07.13
 * Time: 4:05
 * To change this template use File | Settings | File Templates.

 
    Copyright 2013, Vladislav Kovalev

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
   
    */
require_once("class.simpleDB.php");
require_once("class.simpleMysqli.php");
SQLSide($_POST["regid"], $_POST["email"], $_POST["speciality"]);
global $_SQL;
function quote_smart($value)
{
    global $_SQL;
    // если magic_quotes_gpc включена - используем stripslashes
    if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }
    // Если переменная - число, то экранировать её не нужно
    // если нет - то окружем её кавычками, и экранируем
    if (!is_numeric($value)) {
        $value = mysqli_real_escape_string($_SQL->_getObject(), $value);
    }
    return $value;
}

function SQLSide($regid, $email, $speciality)
{
    global $_SQL;
    $settings=array(
        'server' => 'localhost',
        'username' => 'root',
        'password' => '',
        'db' => 'sibgau',
        'port' => 3306,
        'charset' => 'utf8',
    );
    $_SQL=new simpleMysqli($settings);
    $query=$_SQL->update('UPDATE clients set speciality = "'. quote_smart($speciality) .'" where regid = "' . quote_smart($regid) . '" AND email="' . quote_smart($email) . '"');
}
?>