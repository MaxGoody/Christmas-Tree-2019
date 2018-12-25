<?php
require __DIR__.'/src/exceptions.php';
require __DIR__.'/src/autoload.php';

use Application\Requests\Sender;

$sender = new Sender();

# Авторизация
$response = $sender->sendUnsigned('canvas', 'show', [
    'api_id' => 6743634,
    'viewer_id' => 0,
    'auth_key' => ''
]);

$parameters = [
    'aid' => $response['appId'],
    'uid' => $response['uid'],
    'suid' => $response['suid'],
    'authKey' => $response['authKey'],
    'sessionKey' => $response['sessionKey'],
    'version' => $response['version'],
    'clientPlatform' => 'js'
];

# Инициализация
$playerInfo = $sender->sendSigned('game', 'init', $parameters, [
    'friendIds' => [],
    'sessionData' => [
        'sessionId' => $response['fluentSession']
    ]
]);

$parameters['sessionKey'] = $playerInfo['user']['session'];

# Принимаем почту.
$count = count($playerInfo['mail']['inbox']);
if ($count !== 0) {
    $sender->sendSigned('mail', 'flushInbox', $parameters);
}

# Отправляем почту.
$count = count($playerInfo['mail']['requests']);
if ($count !== 0) {
    $sender->sendSigned('mail', 'flushRequests', $parameters);
}

# Собираем генераторы.
foreach ($playerInfo['user']['generators'] as $name => $data) {
    $sender->sendSigned('generator', 'exchange', $parameters, [
        'name' => $name
    ]);
}

# Собираем прибыль с эльфов.
if (count($playerInfo['user']['elf']['collect']) !== 0) {
    $sender->sendSigned('Elf', 'UnloadFromCollect', $parameters);
}

# Запускаем эльфов.
foreach ($playerInfo['user']['elf']['data'] as $id => $data) {
    if ($data['type'] !== 2 && $data['time'] === 0) {
        $sender->sendSigned('Elf', 'Start', [
            'elfId' => $id
        ]);
    }
}

# Собираем квесты.
foreach ($playerInfo['user']['quest'] as $id => $data) {
    if (isset($data['tasks']) === false) {
        continue;
    }

    foreach ($data['tasks'] as $taskId => $value) {
        if (isset($data['progress'][$taskId]) === false) {
            continue;
        }

        if ($data['progress'][$taskId] === $value) {
            $sender->sendSigned('Quest', 'Finish', $parameters, [
                'questLineId' => $id
            ]);
        }
    }
}

# Собираем сундучки.
foreach ($playerInfo['friendsList'] as $id => $data) {
    if (isset($data['hash']) === false) {
        continue;
    }

    $response = $sender->sendSigned('friend', 'get', $parameters, [
        'screenId' => $playerInfo['user']['screen'],
        'sFriendId' => $id,
        'chestId' => 1
    ]);

    if (time() - $response['user']['loginTime'] >= 2592000 ||
        isset($response['chest'][1]) === false ||
        $response['chest'][1]['time'] !== 0) {
        continue;
    }

    $sender->sendSigned('chest', 'open', $parameters, [
        'chestId' => 1,
        'screenId' => $playerInfo['user']['screen'],
        'sFriendId' => $id,
        'hash' => $data['hash']
    ]);

}

# Собираем палочки.
# TODO: Объеденить с верхним циклом.
$count = count($playerInfo['friendsData']['energyGather']);
$friends = [];

foreach ($playerInfo['friendsList'] as $id => $data) {
    if (count($friends) + $count === 50 || isset($data['hash']) === false || $data['allowEnergyGather'] !== 1) {
        continue;
    }
    
    $friends[$id] = $data['hash'];
}

foreach (array_chunk($friends, 10, true) as $chunk) {
    $sender->sendSigned('friend', 'getEnergy', $parameters, [
        'screenId' => $playerInfo['user']['screen'],
        'friendsData'=> $chunk
    ]);
}
