<?php

var_dump($_POST);
if ($_POST) {
    $username = isset($_POST['username']) ? $_POST['username'] : false;
    $password = isset($_POST['password']) ? $_POST['password'] : false;

    echo "$username  |  $password";
}

?>