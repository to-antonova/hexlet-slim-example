<?php
$usersListFile = 'users.json';
$usersList = json_decode(file_get_contents($usersListFile), true);
$id = 'mike';
$result = [];
foreach ($usersList as $user) {
    if ($user['nickname'] == $id) {
        $result = $user;
    }
}
//var_dump($usersList);
var_dump($result['nickname']);
