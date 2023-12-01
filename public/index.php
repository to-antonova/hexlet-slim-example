<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если интересно, то посмотрите DI Container
use DI\Container;

$usersListFile = 'users.json';
$usersList = json_decode(file_get_contents($usersListFile), true);

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users.index');
    $router->urlFor('users.show', ['id' => '']);
    $router->urlFor('users.create');
    $router->urlFor('users.store');
    $router->urlFor('courses.show', ['id' => '']);

    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});


$app->get('/users', function ($request, $response) use ($router, $usersList) {
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($usersList, function ($user) use ($term){
        return str_contains($user['nickname'], $term);
    });
    $urlUsers = $router->urlFor('users.index');
    $urlNewUser = $router->urlFor('users.create');
    $urlUser = [];
    foreach ($usersList as $user) {
        $nickname = $user['nickname'];
        $urlUser[$nickname] = $router->urlFor('users.show', ['id' => $nickname]);
    }
    $params = [
        'users' => $filteredUsers,
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'term' => $term,
        'urlUsers' => $urlUsers,
        'urlNewUser' => $urlNewUser,
        'urlUser' => $urlUser
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

$app->get('/users/new', function ($request, $response) use ($router) {
    $urlUsers = $router->urlFor('users.index');
    $params = [
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'errors' => [],
        'urlUsers' => $urlUsers
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.create');

$app->get('/users/{id}', function ($request, $response, $args) use ($router, $usersList) {
    $id = $args['id'];
    $result = [];
    foreach ($usersList as $user) {
        if ($user['nickname'] == $id) {
            $result = $user;
        }
    }
    if (empty($result)) {
        return $response->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write('Page not found');
    }
    $params = [
        'user' => ['nickname' => $result['nickname'], 'email' => $result['email'], 'id' => $result['id']]
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

$app->post('/users', function ($request, $response) use ($router, $usersListFile) {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();
    $users = json_decode(file_get_contents($usersListFile), true);
    $users[] = $user;
    file_put_contents($usersListFile, json_encode($users, JSON_PRETTY_PRINT));
    return $response->withRedirect($router->urlFor('users.index'), 302);
})->setName('users.store');


$app->get('/courses/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('courses.show');

$app->run();
