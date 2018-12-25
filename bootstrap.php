<?php
require __DIR__.'/src/exceptions.php';
require __DIR__.'/src/autoload.php';

use Application\Requests\Sender;

$sender = new Sender();

# Авторизация
$response = $sender->sendUnsigned('canvas', 'show', [
    'api_id' => 6743634,
    'viewer_id' => 124349821,
    'auth_key' => 'a9f20adc3a819bbe5c5cbfc6e58be2f9'
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
    'friendIds' => [5171724,6099552,17476368,18379198,21306442,28449627,35162158,36577284,36747546,37148972,37203224,39294447,40933191,41644942,41841389,42373175,43385726,43510823,44685177,52136034,59224775,62293359,63255557,63753919,63899748,64125727,65580861,65592966,69408890,69973472,73144504,75822268,77282035,78822598,81030204,87365508,87627918,89217590,90535408,91201661,91460565,92637386,94447309,94724066,98117557,107902188,108621888,111058921,112103026,113704927,115546344,125427198,126056938,127069666,128307200,132905833,133690096,134376050,135630428,135763900,136003409,136371832,136838965,138428859,139742485,140547865,141511280,142244255,142516755,144824221,145080445,147267677,151227228,151467051,152332186,155026193,156070465,156144750,156501744,161097106,163011963,163512944,164086017,165118064,165256710,166783026,167898685,175956051,177644871,179227453,181639435,182020184,187727571,188642409,189851029,191657602,194908789,196922784,201305659,204664443,208014661,208651714,209671439,213465125,214720808,216935142,219121351,227129833,232565696,235978866,238617205,243586795,244065932,246807709,260560549,263325449,270848606,278467964,282055134,285659107,287015355,299211328,303180434,310019492,316635348,339882092,348580766,351722902,375447702,401990315,432662330,456344239,468554973,468749001,469586605,516575881],
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
        $sender->sendSigned('Elf', 'Start', $parameters, [
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

    try {
        $sender->sendSigned('chest', 'open', $parameters, [
            'chestId' => 1,
            'screenId' => $playerInfo['user']['screen'],
            'sFriendId' => $id,
            'hash' => $data['hash']
        ]);
    } catch (Exception $exception) {
        if ($exception->getCode() === 202) {
            break;
        }
        throw $exception;
    }

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
