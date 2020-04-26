<?php
$db = mysqli_connect("localhost", "root", "root", "protest14") or die("Нет соединения с БД");
mysqli_set_charset($db, "utf8") or die("Не установлена кодировка соединения");