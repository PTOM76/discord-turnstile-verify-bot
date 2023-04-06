<?php
include __DIR__.'/vendor/autoload.php';
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;

if (!isset($argv)) {exit;};
$guildId = $argv[1];
$roleId = $argv[2];
$userId = $argv[3];

$client = new Discord(['token' => getenv('TOKEN'), 'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS, 'loadAllMembers' => true]);
$client->on('ready', function($discord) {
  global $guildId;
  $discord->guilds->fetch($guildId)->done(function (Guild $guild) {
    global $roleId;
    $guild->roles->fetch($roleId)->done(function (Role $role) use($guild) {
      global $userId;
      $guild->members->fetch($userId)->done(function (Member $member) use($role) {
        $member->addRole($role)->done(function () {
          exit;
        });
        
      });
    });
  });
});

$client->run();