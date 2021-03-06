<?php
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

//use Slim\Factory\AppFactory;

//$app = AppFactory::create();  // объект приложения без контейнера
//$app->addErrorMiddleware(true, true, true);

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container); // объект приложения с контейнером DI $container
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$users = ['mike', 'Mishel', 'adel', 'keks', 'kamila'];
$app->get('/users', function ($request, $response ) use ($users) {
    $name = $request->getQueryParam('name', '');

    //$res = stripos('mike', 'mi'); // stripos вернет позицию. тут 0. и в условии false будет хоть и нашел вхождение
    //поэтому удобнее stristr - вернет строку или false
    if (!empty($name)) {
        $users = array_map(function ($user) use ($name) {
            //return stripos($user, $name) !== false  ? $user : ''; // тоже рабочий !== FALSE  (recommended для stripos)
            return stristr($user, $name) ? $user : '';
        }, $users);
    }
    $params = [
        'users' => $users,
        'name'  => $name ?? "",
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
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
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});
/*
$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});
*/
$app->run();