<?php


namespace App02;


class Validator
{
    public function __construct()
    {
    }
    public function validate($data): array
    {
        // все непустые
        return [];
        //return [5];
        //return ['email' => "bad adress", 'password' => "ne olo passwords"];
    }
}