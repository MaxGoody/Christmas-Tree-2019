<?php
require __DIR__.'/src/exceptions.php';
require __DIR__.'/vendor/autoload.php';

use Application\Config;
use Application\Requests\Sender;

# Load configuration...
Config::loadFromFile(__DIR__.'/config.json');

$sender = new Sender();

# Authenticate...
$response = $sender->sendUnsigned('canvas', 'show', [
    'api_id' => 6743634,
    'viewer_id' => Config::get('id'),
    'auth_key' => Config::get('authKey')
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

# Initialize session...
$playerInfo = $sender->sendSigned('game', 'init', $parameters, [
    'friendIds' => Config::get('friends'),
    'sessionData' => [
        'sessionId' => $response['fluentSession']
    ]
]);

$parameters['sessionKey'] = $playerInfo['user']['session'];

# Accept mail...
if (Config::get('mail.in') === true) {
    $count = count($playerInfo['mail']['inbox']);
    if ($count !== 0) {
        $sender->sendSigned('mail', 'flushInbox', $parameters);
    }
}

# Send mail...
if (Config::get('mail.out') === true) {
    $count = count($playerInfo['mail']['requests']);
    if ($count !== 0) {
        $sender->sendSigned('mail', 'flushRequests', $parameters);
    }
}

# Collect generators...
if (Config::get('generators') === true) {
    foreach ($playerInfo['user']['generators'] as $name => $data) {
        $sender->sendSigned('generator', 'exchange', $parameters, [
            'name' => $name
        ]);
    }
}

# Collect elfs...
if (Config::get('elf.collect') === true) {
    if (count($playerInfo['user']['elf']['collect']) !== 0) {
        $sender->sendSigned('Elf', 'UnloadFromCollect', $parameters);
    }
}

# Start new elfs...
if (Config::get('elf.start') === true) {
    foreach ($playerInfo['user']['elf']['data'] as $id => $data) {
        if ($data['type'] !== 2 && $data['time'] === 0) {
            $sender->sendSigned('Elf', 'Start', $parameters, [
                'elfId' => $id
            ]);
        }
    }

}

# Collect completed quests...
if (Config::get('quests') === true) {
    foreach ($playerInfo['user']['quest'] as $id => $data) {
        if (isset($data['tasks']) === false) {
            continue;
        }

        foreach ($data['tasks'] as $taskId => $value) {
            if (isset($data['progress'][$taskId]) === false) {
                continue;
            }

            if ($data['progress'][$taskId] >= $value) {
                $sender->sendSigned('Quest', 'Finish', $parameters, [
                    'questLineId' => $id
                ]);
            }
        }
    }
}

# Collect friends chests...
if (Config::get('chests') === true) {
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
}

# Collect friends magic wands.
if (Config::get('wands') === true) {
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

}
