<?php

//use Slim\Factory\AppFactory;
//$app = AppFactory::create();  // объект приложения без контейнера
//$app->addErrorMiddleware(true, true, true);

namespace App02;
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use DI\Container;
//use App02\UserRepository;

// Старт PHP сессии
session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container); // объект приложения с контейнером DI $container
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class); // включить поддержку переопределения метода в самом Slim

// Получаем роутер – объект отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
})->setName('main');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];

    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('newUser');

//$repo = new \App02\UserRepository(); //на файлах
$repo = new \App02\UserRepositoryCookies();

$app->get('/users', function ($request, $response ) use ($repo) {
    //для формы поиска после отправки
    /*
    $name = $request->getQueryParam('name', '');

    //$res = stripos('mike', 'mi'); // stripos вернет позицию. тут 0. и в условии false будет хоть и нашел вхождение
    //поэтому удобнее stristr - вернет строку или false
    if (!empty($name)) {
        $users = array_filter($users, function ($user) use ($name) {
            //return stripos($user, $name) !== false  ? $user : ''; // тоже рабочий !== FALSE  (recommended для stripos)
            return stristr($user, $name) ? $user : '';
        });
    }
    */

    //$users = $repo->all(); // на файлах
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    //print_r($users); die;

    $params = [
        'users' => $users,
        'name'  => $name ?? "",
    ];

    // Извлечение flash сообщений установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages(); //Array ( [success] => Array ( [0] => users/new This is a message ) )
    //print_r($messages); // => ['success' => ['This is a message']]
    $params['flash'] = $messages;

    if(empty($users)) {
        return $this->get('renderer')->render($response, 'users/index.phtml', $params)->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/{id}', function ($request, $response , $args) use ($repo) {
    $id = $args['id'];

    //на куках
    $usersCookie = $request->getCookieParam('users', json_encode([]));
    $user = $repo->findById($id, $usersCookie);

    //$user = $repo->findById($id); // на файлах
    $params = [
        'user' => $user
    ];

    if (empty($user)) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'user/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router, $repo) {
    //$validator = new Validator();
    // Извлекаем данные формы
    $userData = $request->getParsedBodyParam('user');

    // Проверяем корректность данных
    $validator = new \App02\Validator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        // Если данные корректны, то сохраняем, добавляем флеш и выполняем редирект
        //на куках
        $usersCookie = $request->getCookieParam('users', json_encode([]));
        $encodedUsers = $repo->save($userData, $usersCookie);

        //на файлах
        //$repo->save($userData);

        $this->get('flash')->addMessage('success', 'My Users has been created');
        $url = $router->urlFor('users');
        //return $response->withRedirect($url); // на файлах
        return $response->withHeader('Set-Cookie', "users={$encodedUsers}; Path=/")
            ->withRedirect($url); //на куках // Path чтобы не создавал куку на каждый адрес, а только на корень /
    }

    $params = [
        'user' => $userData,
        'errors' => $errors
    ];

    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);

    /*$this->get('flash')->addMessage('success', 'users/new This is a message');
    return $response
        ->withHeader('Location', $router->urlFor('users'))
        ->withStatus(302);
    */
});

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];

    //на куках
    $usersCookie = $request->getCookieParam('users', json_encode([]));
    $user = $repo->findById($id, $usersCookie);
    //$user = $repo->findById($id); //на куках

    $params = [
        'user' => $user,
        'errors' => []
    ];

    // Извлечение flash сообщений установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    $params['flash'] = $messages;

    return $this->get('renderer')->render($response, 'user/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    //$user = $repo->findById($id); //на файлах
    //на куках
    $usersCookie = $request->getCookieParam('users', json_encode([]));
    $user = $repo->findById($id, $usersCookie);

    $data = $request->getParsedBodyParam('user');


    $validator = new \App02\Validator();
    $errors = $validator->validate($data);
    if (count($errors) === 0) {
        // Ручное копирование данных из формы в нашу сущность
        $user['id']   = $id;
        $user['name'] = $data['name'];
        $user['email'] = $data['email'];
        $user['password'] = $data['password'];
        $user['passwordConfirmation'] = $data['passwordConfirmation'];
        $user['city'] = $data['city'];

        $this->get('flash')->addMessage('success', 'user has been updated');
        //print_r($user); die;
        //$repo->save($user,$usersCookie, $id); // на файлах
        $encodedUsers = $repo->save($user,$usersCookie, $id);
        $url = $router->urlFor('editUser', ['id' => $user['id']]);
        //return $response->withRedirect($url); // на файлах
        return $response->withHeader('Set-Cookie', "users={$encodedUsers}; Path=/")
            ->withRedirect($url); //на куках
    }
    $params = [
        'userData' => $data,
        'user' => $user,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'user/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response , $args) use ($repo , $router) {
    $id = $args['id'];
    // алерт добавить в шаблон
    //$repo->destroy($id); //на ф

    //на куках
    $usersCookie = $request->getCookieParam('users', json_encode([]));
    $encodedUsers = $repo->destroy($id, $usersCookie);

    $this->get('flash')->addMessage('success', 'Userse has been deleted');
    //return $response->withRedirect($router->urlFor('users'));
    return $response->withHeader('Set-Cookie', "users={$encodedUsers}; Path=/")
        ->withRedirect($router->urlFor('users')); //на куках
})->setName('delUser');

$app->post('/auth', function ($request, $response) use ($router, $repo) {
    //$validator = new Validator();
    // Извлекаем данные формы
    $authData = $request->getParsedBodyParam('auth');
    //print_r($authData); die;

    // Проверяем корректность данных
    $validator = new \App02\Validator();
    $errors = $validator->validate($authData);

    $email = $authData['email'];
    //на куках
    $usersCookie = $request->getCookieParam('users', json_encode([]));
    $userAuth = $repo->findByEmail($email, $usersCookie);

    if (!empty($userAuth)) {

        $this->get('flash')->addMessage('success', 'Login has been');
        //$url = $router->urlFor('users', ['id' => $userAuth['id']]);
        // Добавление нового товара
        $_SESSION['Auth'][] = $userAuth;
        $url = $router->urlFor('users', ['userData' => $userAuth]);
        return $response->withRedirect($url);
    }

    $params = [
        'auth' => $authData,
        //'user' => $user,
        'errors' => [$errors, "authemail" => "нет емейла такого"]
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);

});

/*
 * выход
    $_SESSION = []
 * */


//$courses = ["mat", "lit"];
$courses = [
    ["id" => 1,"name" => "math"],
    ["id" => 2,"name" =>'geom'],
    ["id" => 3,"name" =>'khimi'],
];

$app->get('/courses', function ($request, $response) use ($courses) {
    $params = [
        'courses' => $courses
    ];
    return $this->get('renderer')->render($response, 'courses/index.phtml', $params);
})->setName('courses');

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');
/*
$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');
*/
$app->run();

/*
 * Приведите маршруты и их имена в соответствии с указанной выше схемой.
Переделайте получение пользователей так, чтобы данные о пользователях брались из файла. Не забудьте предотвратить XSS.
 * */