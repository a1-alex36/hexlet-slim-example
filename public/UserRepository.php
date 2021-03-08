<?php


namespace App02;

class UserRepository
{
    const FILE_USERS = __DIR__ . '/users.txt';
    //const LIST_USERS = ['mike', 'Mishel', 'adel', 'keks', 'kamila'];

    public function __construct() {

    }

    public function all(): array
    {
        // построчно в массив из файла
        $current = file_get_contents(self::FILE_USERS);
        $current = explode(PHP_EOL, $current);

        $users2 = array_map(function ($user) {
            //return json_decode($user)->name; // в объект
            return json_decode($user, true); // в массив
        }, $current);
        //print_r($users2);
        //return [...self::LIST_USERS, ...$users2]; // всех в 1 массив
        return $users2; // всех в 1 массив
    }

    public function findById( int $id ) {
        // повторяются надо выновить. в конструктор или в статич методы
        // что-то типо метода нормалайз или декоде для данных для всех методов потом юзать его.
        $current = file_get_contents(self::FILE_USERS);
        $current = explode(PHP_EOL, $current);
        //print_r($current);
        // по логике подходит фильтр. т.к. надо просто выбрать нужный ИД
        //но есть 2 задача - декод в массив для результата, а фильтр вернет тот элемент который в function ($user)
        // а декод идет в теле для сравнения, поэтому не учитывается в результате при арр.фильтр
        // решил так - и мап и потом фильтр по непустым
        /*
        return array_filter( array_map( function ($user) use ($id) {
                        $user = json_decode($user, true);
                        return $user["id"] == $id ? $user : "";
                    }, $current),
                    function ($user) {
                        return !empty($user);
                    });
        */

        // фильтр + мап заменяется одним редьюсом
        return array_reduce( $current, function ($res, $user) use ($id) {
            $user = json_decode($user, true);
            // накопит и вернет все совпадения
            /*
            if($user["id"] == $id) {  //нестрогое т.к. стринг и инт(из урл)
                $res[] = $user;
            }
            return $res;
            */
            // последнее совпадение - одном. массив  // $user     // Array(...val...)
            // последнее совпадение - многом. массив // [$user]   // Array([0] => Array(...val...)) // новый массив каждый раз, перезатирает
            // все совпадения, аналог верхнего ифа   // [...$res, $user] // Array([0] => Array(...val...), [1] => Array(...val...))
            //print_r($res);
            return $user["id"] == $id ? [$user] : $res;
            // что выбирать ? по логике что ожидаем.?
            // $user .точно один из БД по ИД например? - зная что в БД оно точно одно
            // [$user] последний из подходящих. сортировка играет рольтогда. устраивает она?
            // если нужны все, то 3 вариант
        }, []);
    }

    public function save($userData)
    {    //print_r($user);
        $user = json_encode($userData); //json_decode
        // Открываем файл для получения существующего содержимого
        $current = file_get_contents(self::FILE_USERS);
        // Добавляем нового человека в файл
        $current .= $user . "\n";
        // Пишем содержимое обратно в файл
        file_put_contents(self::FILE_USERS, $current);
    }


}