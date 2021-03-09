<?php


namespace App02;


class UserRepositoryCookies
{
    const USERS = __DIR__ . '/users.txt';
    //const LIST_USERS = ['mike', 'Mishel', 'adel', 'keks', 'kamila'];

    public function __construct() {

    }

    public function all(): array
    {
        //$cart = json_decode($request->getCookieParam('cart', json_encode([])));
        return [];
    }

    public function findById( int $id, $usersCookie ) {
        $usersCookie = json_decode($usersCookie, true);

        return array_reduce( $usersCookie, function ($res, $user) use ($id) {
            return $user["id"] == $id ? $user : $res;
        }, []);
        // тут надо вернуть один ассоц массив
        // map вернет  структуру с индекс массивом
        // фильтр вернет туже структуру
    }
    public function findByEmail( string $email, $usersCookie )
    {
        $usersCookie = json_decode($usersCookie, true);

        return array_reduce($usersCookie, function ($res, $user) use ($email) {
            return $user["email"] == $email ? $user : $res;
        }, []);
    }


    public function save($user, $usersCookie, $id = null)
    {
        // взять текущую куки, декод её и  дописать в массив данные
        $user["id"] = $id ?? rand(500, 999);
        $usersCookie = json_decode($usersCookie, true);
        if(!empty($id)) {
            //print_r($usersCookie); die;
            $usersCookie = array_reduce( $usersCookie, function ($res, $item) use ($user, $id) {
                return $item["id"] == $id ? [...$res, $user] : [...$res, $item];
            }, []);
        } else {
            $usersCookie[] = $user;
        }

        //print_r($usersCookie); die;
        return json_encode($usersCookie);
    }

    public function destroy($id, $usersCookie)
    {
        // взять текущую куки, декод её и  дописать в массив данные
        if(!empty($id)) {
            $usersCookie = json_decode($usersCookie, true);
            //print_r($usersCookie); die;
            $usersCookie = array_reduce($usersCookie, function ($res, $item) use ($id) {
                if($item["id"] != $id) {
                    $res[] = $item;
                }
                return $res;
            }, []);
            //print_r($usersCookie); die;
        }
        return json_encode($usersCookie);
    }

}