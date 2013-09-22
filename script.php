<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vladislav
 * Date: 27.07.13
 * Time: 21:14
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
require_once("class.phpmailer.php");
require_once("class.smtp.php");
include('simple_html_dom.php');
$_SPECIALITY;
$_CURRATE;
$_OLDRATE;
$_REGID;
$_EMAIL;
$_FULLSPEC;
$_FIO;
SQLSide();
function SQLSide()
{
    global $_SPECIALITY, $_CURRATE, $_OLDRATE, $_REGID, $_EMAIL, $_FULLSPEC, $_FIO;
    $settings=array(
        'server' => 'localhost',
        'username' => 'root',
        'password' => '',
        'db' => 'sibgau',
        'port' => 3306,
        'charset' => 'utf8',
    );
    $_SQL=new simpleMysqli($settings);
    $query=$_SQL->select('SELECT * FROM clients WHERE speciality IS NOT NULL');
    var_dump($query);

    for ($x=-1; $x++<(count($query)-1);)
    {
        var_dump($x);
        var_dump(count($query));
       $deepquery = $query[$x];
       var_dump($deepquery);
       $_SPECIALITY = $deepquery['speciality'];
       $_OLDRATE = $deepquery['currate'];
       $_REGID = $deepquery['regid'];
       $_EMAIL = $deepquery['email'];
       GetHTML($_REGID);

       if ($_CURRATE != $_OLDRATE)
        {
            echo 'КОРОЧЕ ТУТ ПОЧТУ ОТПРАВЛЯЕМ ТИПА РЭЙТИНГ ИЗМЕНИЛСЯ';
            SendMail($_EMAIL, $_OLDRATE, $_CURRATE, $_FULLSPEC, $_FIO);
            $query=$_SQL->update('UPDATE clients set currate = "'. $_CURRATE.'" where regid = "' . $_REGID . '" AND email="' . $_EMAIL . '"');
            var_dump($query);

        }

    }


}

function SendMail($email, $oldrate, $currate, $fullspec, $fio)
{

    $mail = new PHPMailer(true);

    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->IsHTML(true);
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->SMTPDebug = 2;
    $mail->CharSet = "UTF-8";
    $mail->SMTPSecure = "ssl"; // sets the prefix to the servier
    $mail->Host = "smtp.yandex.ru"; // sets GMAIL as the SMTP server
    $mail->Port = 465; // set the SMTP port for the GMAIL server
    $mail->Username = "username"; // GMAIL username
    $mail->Password = "password"; // GMAIL password


    //Typical mail data
    $mail->AddAddress($email, $fio);
    $mail->SetFrom("gaubot@snouwer.ru", "Бот рейтинга СибГАУ");
    $mail->Subject = "Уважаемый абитуриент, Ваш рейтинг изменился!";
    $mail->Body = '<html>
    <head>
        <title>Абитуриент СибГАУ 2013 | Ваш рейтинг изменился</title>
    </head>
    <body>
        <p>Здравствуйте, '.$fio.'.</p>
        <p>Ваш рейтинг на специальность <b>"'. $fullspec . '</b>" изменился.</p>
        <p>Был <b>'. $oldrate. '</b>, стал <b> '. $currate.'</b>.</p>
    </body>
</html>';

    try{
        $mail->Send();
        echo "Success!";
    } catch(Exception $e){
        //Something went bad
        echo "Fail :(";
    }
}

function GetHTML($regid)
{
    $ch = curl_init('http://abiturient.sibsau.ru/rating/rating.php');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'reg_num=' . $regid . '&view=yes');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $logres = curl_exec($ch);
    ParseHTML($logres);
}

function ParseHTML($source)
{
    global $_SPECIALITY, $_CURRATE, $_FULLSPEC, $_FIO;
    $context = stream_context_create();
    stream_context_set_params($context, array('Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'));
    $html = str_get_html($source, 0, $context);
    $_ROWDATA = array();
    foreach($html->find('table[background=#0cdcdc]', 0)-> children() as $tr) {
        foreach ($tr->children() as $td) {
            $_ROWDATA[] = $td->innertext;
        }
    }
    $_CURRATE  = $_ROWDATA[10 * $_SPECIALITY + 1];
    $_FULLSPEC = $_ROWDATA[10 * $_SPECIALITY + 9];
    $_FIO      = $_ROWDATA[14];
}