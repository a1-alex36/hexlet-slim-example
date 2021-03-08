<?php

//use Slim\Factory\AppFactory;
//$app = AppFactory::create();  // объект приложения без контейнера
//$app->addErrorMiddleware(true, true, true);

namespace App02;
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
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
})->setName('users-new');

$repo = new \App02\UserRepository();

$app->get('/users', function ($request, $response ) use ($repo) {
    //для формы после отправки
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

    $users = $repo->all();

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
    $user = $repo->findById($id);
    $params = [
        'user' => $user
    ];

    if (empty($user)) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'user/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router) {
    //$validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    /*$errors = $validator->validate($user);
    if (count($errors) === 0) {
        $repo->save($user);
        return $response->withRedirect('/users', 302);
    }
    */
    //print_r($user);
    $user = json_encode($user); //json_decode
    $file = __DIR__ . '/users.txt';
    // Открываем файл для получения существующего содержимого
    $current = file_get_contents($file);
    // Добавляем нового человека в файл
    $current .= $user . "\n";
    // Пишем содержимое обратно в файл
    file_put_contents($file, $current);

    $this->get('flash')->addMessage('success', 'users/new This is a message');
    return $response
        ->withHeader('Location', $router->urlFor('users'))
        ->withStatus(302);
});

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