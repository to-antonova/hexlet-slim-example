<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use App\Validator;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если интересно, то посмотрите DI Container
use DI\Container;

session_start();

$usersListFile = 'users.json';
$usersList = json_decode(file_get_contents($usersListFile), true);

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/router', function ($request, $response) use ($router) {
    $router->urlFor('users.index');
    $router->urlFor('users.show', ['id' => '']);
    $router->urlFor('users.create');
    $router->urlFor('users.store');
    $router->urlFor('users.destroy');
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/', function ($request, $response) {
    phpinfo();
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
    $messages = $this->get('flash')->getMessages();
    $params = [
        'users' => $filteredUsers,
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'term' => $term,
        'urlUsers' => $urlUsers,
        'urlNewUser' => $urlNewUser,
        'urlUser' => $urlUser,
        'flash' => $messages
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
    $resultUser = [];
    foreach ($usersList as $user) {
        if ($user['nickname'] == $id) {
            $resultUser = $user;
            $urlEditUser = $router->urlFor('users.edit', ['id' => $user['nickname']]);
            $urlDeleteUser = $router->urlFor('users.destroy', ['id' => $user['nickname']]);
        }
    }
    if (!$resultUser) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $urlUsers = $router->urlFor('users.index');

    $messages = $this->get('flash')->getMessages();
    $params = [
        'user' => $resultUser,
        'urlEditUser' => $urlEditUser,
        'urlDeleteUser' => $urlDeleteUser,
        'urlUsers' => $urlUsers,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');


$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($router, $usersList) {
    $id = $args['id'];
    $resultUser = [];
    foreach ($usersList as $user) {
        if ($user['nickname'] == $id) {
            $resultUser = $user;
        }
    }
    if (!$resultUser) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $params = [
        'user' => $resultUser,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('users.edit');


$app->post('/users', function ($request, $response) use ($router, $usersListFile) {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();

    $validate = new \App\Validator();
    $errors = $validate->validate($user);
    if (count($errors) === 0) {
        $users = json_decode(file_get_contents($usersListFile), true);
        $users[] = $user;
        file_put_contents($usersListFile, json_encode($users, JSON_PRETTY_PRINT));
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withRedirect($router->urlFor('users.index'));
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.store');


$app->patch('/users/{id}', function ($request, $response, array $args) use ($router, $usersList, $usersListFile)  {
    $id = $args['id'];

    $user = [];
    foreach ($usersList as $userInList) {
        if ($userInList['nickname'] == $id) {
            $user = $userInList;
        }
    }
    if (!$user) {
        return $response->write('Page not found')
            ->withStatus(404);
    };

    $data = $request->getParsedBodyParam('user');
    $validate = new \App\Validator();
    $errors = $validate->validate($data);

    if (empty($errors)) {
        $updatedUserList = [];
        foreach ($usersList as $userInList) {
            if ($userInList['id'] == $user['id']) {
                $userInList['nickname'] = $data['nickname'];
                $userInList['email'] = $data['email'];

            }
            $updatedUserList[] = $userInList;
        }

        file_put_contents($usersListFile, json_encode($updatedUserList, JSON_PRETTY_PRINT));

        $this->get('flash')->addMessage('success', 'User has been updated');
        return $response->withRedirect($router->urlFor('users.show', ['id' => $data['nickname']]));
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('users.update');


$app->delete('/users/{id}', function ($request, $response, array $args) use ($router, $usersList, $usersListFile) {
    $id = $args['id'];
    $updatedUserList = [];
    foreach ($usersList as $userInList) {
        if ($userInList['id'] == $id) {
            unset($userInList);
        } else {
            $updatedUserList[] = $userInList;
        }
    }

    file_put_contents($usersListFile, json_encode($updatedUserList, JSON_PRETTY_PRINT));

    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users.index'));
})->setName('users.destroy');

$app->run();
