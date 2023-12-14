<?php

namespace App;

class Validator
{
    public function validate($user)
    {
        $errors = [];
        if ($user['nickname'] == '') {
            $errors['nickname'] = "Can't be blank";
        }

        if ($user['email'] == '') {
            $errors['email'] = "Can't be blank";
        }

//        if ($user['password'] == '') {
//            $errors['password'] = "Can't be blank";
//        }
//
//        if ($user['passwordConfirmation'] == '') {
//            $errors['passwordConfirmation'] = "Can't be blank";
//        }
//
//        if ($user['passwordConfirmation'] !== $user['password']) {
//            $errors['passwordDoNotMatch'] = "password do not match";
//        }
        return $errors;
    }
}