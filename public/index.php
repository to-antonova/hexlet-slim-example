<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если интересно, то посмотрите DI Container
use DI\Container;

$usersListFile = 'users.json';

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});


$app->get('/users', function ($request, $response) use ($usersListFile) {
    $term = $request->getQueryParam('term');
    $users = json_decode(file_get_contents($usersListFile), true);
//    $users = [["nickname" => "mike","email" => "mike@mail.ru","id" => "0000000000001"]];
    $filteredUsers = array_filter($users, function ($user) use ($term){
        return str_contains($user['nickname'], $term);
    });
    $params = [
        'users' => $filteredUsers,
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['users' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) use ($usersListFile) {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();
    $users = json_decode(file_get_contents($usersListFile), true);
    $users[] = $user;
    file_put_contents($usersListFile, json_encode($users, JSON_PRETTY_PRINT));
    return $response->withRedirect('/users', 302);
});


$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->run();
