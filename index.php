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

include('simple_html_dom.php');
require_once("class.simpleDB.php");
require_once("class.simpleMysqli.php");

$_HTMLSOURCE;
$_ROWDATA;
$_SQL;
$_SPECIALITY;

if (!empty($_GET['regid']) && !empty($_GET['email'])) {
    if (!@function_exists(curl_init)) {
        echo '<center><b>cURL not Supported</b></center><br>';
        exit;

    }
    GetInfo(mb_strtoupper($_GET['regid'], "utf-8"), $_GET['email']);
} else {
    Form();
    exit;
}

function GetInfo($regid, $email)
{
    global $_HTMLSOURCE, $_ROWDATA;
    try {
        GetHTML($regid);
        ParseHTML($_HTMLSOURCE);
        ParseData($regid, $email);
    }
    catch(exception $e)
    {

    }
}

function quote_smart($value)
{
    GLOBAL $_SQL;
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

function GetHTML($regid)
{
    global $_HTMLSOURCE;
    $ch = curl_init('http://abiturient.sibsau.ru/rating/rating.php');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'reg_num=' . $regid . '&view=yes');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $logres = curl_exec($ch);
    $_HTMLSOURCE = $logres;
}

function ParseHTML($source)
{
    global $_ROWDATA;
    $context = stream_context_create();
    stream_context_set_params($context, array('Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'));
    $html = str_get_html($source, 0, $context);
    $_ROWDATA = array();
    foreach($html->find('table[background=#0cdcdc]', 0)-> children() as $tr) {
        foreach ($tr->children() as $td) {
            $_ROWDATA[] = $td->innertext;
        }
    }
}

function ParseData($regid, $email)
{
    global $_ROWDATA;
    $fio =  @$_ROWDATA[14];
    if ($fio != "")
    {
        Head();
        if (!SQLSide($regid, $email))
        {
            SQLCreateUser($regid, $email);
            echo <<<HTML

                              <div class="alert alert-info">
                        <a class="close" data-dismiss="alert" href="#">×</a>Вы были успешно зарегистрированы в системе.
                    </div>
HTML;
        }
        else
        {

        }
        BotBody($_ROWDATA);;
    }
    else
    {
        Head();
        echo <<<HTML

                              <div class="alert alert-error">
                        <a class="close" data-dismiss="alert" href="#">×</a>Не удалось получить рейтинг. Проверьте правильность регистрационного кода!
                    </div>
HTML;
        Body();
  }
  }


function SQLSide($regid, $email)
{
    global $_SQL, $_SPECIALITY;
    $settings=array(
        'server' => 'localhost',
        'username' => 'root',
        'password' => '',
        'db' => 'sibgau',
        'port' => 3306,
        'charset' => 'utf8',
    );
    $_SQL=new simpleMysqli($settings);
    $query=$_SQL->select('SELECT * FROM clients WHERE regid="' . quote_smart($regid) . '" AND email="' . quote_smart($email) . '"');
    if (empty($query))
    {
        echo 'ПИЗДЕЦ НЕ НАЙДЕНО';
        return false;
    }
    else
    {
        $pidquery = $query[0];
        if ($pidquery['speciality'] != null)
        {
            $_SPECIALITY = $pidquery['speciality'];
        }
        return true;

    }

}

function SQLCreateUser($regid, $email)
{
    global $_SQL;
    $query=$_SQL->insert("INSERT INTO clients (regid, email) VALUES ('". quote_smart($regid) . "', '". quote_smart($email). "')");
echo $query;
    return $query;
}

function Form()
{
    Head();
    Body();
}

function Head()
{
    echo <<<HTML
    <!DOCTYPE html>
<html>
    <head>
        <title>Бот рейтингов абитуриентов СибГАУ</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="description" content="Бот для автоматического оповещения о текущем рейтинге абитуриента в СибГАУ"/>
        <meta name="keywords" content="sibsau, сибгау, рейтинг, бот" />
        <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="css/custom.css" rel="stylesheet" media="screen">
        <script type="text/javascript">

        function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
            function saveClicked()
            {
                        $.post('data.php',{ regid:getParameterByName("regid"), email : getParameterByName("email"),
                        speciality: $('input[name="speciality"]:checked').val() }, function(response){
                             $("#data").html(response); })

            }
        </script>
    </head>
    <body>
HTML;
}

function Body() {
    echo <<<HTML

        <div class="container">
        <div class="row well" id="login">
        <div class="span7">
                            <legend>Описание</legend>
                                <p>Устали постоянно мониторить свой рейтинг в СибГАУ?</p>
                        <p>Не беда! Данный бот автоматически будет присылать Вам уведомление на электронную почту при любом изменении Вашего рейтинга.</p>
                        <p>Для его работы Вам нужно указать всего-лишь свой регистрационный код (например, БПИ-0000000) и желаемый электронный ящик, куда будут присылаться уведомления.</p>
                  </div>
                <div class="span4">
                    <legend>Вход</legend>
    <form method="GET" action="" accept-charset="UTF-8">
                        <input type="text" id="regid" class="span4" name="regid" placeholder="Регистрационный номер">
                        <input type="text" id="email" class="span4" name="email" placeholder="Ваша электронная почта">
                        <button type="submit" class="btn btn-info btn-block">Войти</button>
                        <br>
                        </form>
                </div>
        </div>
HTML;
    Footer();
}

function Footer()
{
   echo <<<HTML
            <footer>
                <center><a href="http://me.snouwer.ru"><p style="color:#7A7A7A">Vladislav 'SnoUweR' Kovalev, 2013 г.</p></a></center>
            </footer>
        <script src="http://code.jquery.com/jquery-latest.js"></script>
        <script src="js/bootstrap.min.js"></script>
    </body>
</html>
HTML;

}

function BotBody($data)
{
    global $_SPECIALITY;
           echo <<<HTML


        <div class="container">
            <div class="row">
                <div class="span12 well" id="info">
                    <legend>Бот рейтингов абитуриентов СибГАУ 2013</legend>
HTML;
    echo '<p>Здравствуйте, '. $data[14];
    echo '<p>Выберите, пожалуйста, какую специальность Вы бы хотели отслеживать</p>';
    echo '<p><input type="radio" name="speciality" value="1" '; if ($_SPECIALITY == 1) { echo ' checked'; } echo '> '.$data[19].': <b>' . $data[11] . ' бюджетных мест.</b></p>';
    if (@$data[21] != null) { echo '<p><input type="radio" name="speciality" value="2" '; if ($_SPECIALITY == 2) { echo ' checked'; } echo '> '.$data[29].': <b>' . $data[21] . ' бюджетных мест.</b></p>'; }
    if (@$data[31] != null) { echo '<p><input type="radio" name="speciality" value="3" '; if ($_SPECIALITY == 3) { echo ' checked'; } echo '> '.$data[39].': <b>' . $data[31] . ' бюджетных мест.</b></p>'; }
    echo '<form method="POST" action="">';
    echo '<button name="savespec" type="button" onclick="saveClicked()" class="btn btn-info btn-block">Сохранить</button>';
    echo '</form>';
    echo '<div id="data"></div>';
    echo <<<HTML
</div>
                </div>
            </div>
        </div>
HTML;
    Footer();

}
?>