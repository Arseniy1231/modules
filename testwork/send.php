<?php
include 'db.php';




$name = mysqli_real_escape_string($db, $_POST['name']);
$email = mysqli_real_escape_string($db, $_POST['email']);
$region = mysqli_real_escape_string($db, $_POST['region']);
$city = mysqli_real_escape_string($db, $_POST['city']);
if (!empty($_POST['district'])) {
    $district = mysqli_real_escape_string($db, $_POST['district']);
    $query_second = mysqli_query($db, "SELECT ter_name  FROM t_koatuu_tree WHERE (ter_id = '{$region}') or (ter_id = '{$city}') or (ter_id = '{$district}')");
} else {
    $query_second = mysqli_query($db, "SELECT ter_name  FROM t_koatuu_tree WHERE (ter_id = '{$region}') or (ter_id = '{$city}') ");
}
$query = mysqli_query($db, "SELECT * FROM `users` WHERE email ='{$email}'");
$numr = mysqli_num_rows($query);
if ($numr == 0) {
    if (!empty($_POST['district'])) {
        $sql_q = "INSERT INTO `users` (`name`,email,region,city,district) VALUES('{$name}','{$email}','${region}', '${city}', '{$district}')";
    } else {
        $district= ' ';
        $sql_q = "INSERT INTO `users` (`name`,email,region,city,district) VALUES('{$name}','{$email}','${region}', '${city}', '{$district}')";
    }
    $res = mysqli_query($db, $sql_q);
    if ($res) {
        echo "Аккаунт успешно создан";
    } else {
        echo "Не удалось добавить информацию";
    }
} else {
    echo "Вы уже зарегестрированы";
    echo "<br>";
    echo "Ваши данные:";
    echo "<br>";
    foreach ($query as $quer) {
        echo "Имя         ";
        echo $quer['name'];
        echo "<br>";
        echo "Email       ";
        echo $quer['email'];
        echo "<br>";
        echo "Aдрес      ";
        foreach ($query_second as $quer_second) {
            echo "<br>";
            echo $quer_second['ter_name'];
            echo ",       ";
        }
    }
}
