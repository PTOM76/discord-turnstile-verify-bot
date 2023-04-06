<?php
// https://discord.com/api/oauth2/authorize?client_id=1092461471818068059&permissions=8&scope=bot%20applications.commands
file_put_contents("time.txt", time());

include __DIR__.'/vendor/autoload.php';
use Discord\X;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ActionRow;
use Discord\Parts\Permissions\RolePermission;

$lang = json_decode(file_get_contents("lang.json"), true);

$token = getenv("TOKEN");

$clientId = "";
$clientUser = "";

$client = new Discord([
  'token' => $token,
]);

$client->on('ready', function($discord) {
  echo "Bot is ready.", PHP_EOL;
  $clientUser = $discord->user;
  $clientId = $clientUser->id;

  $discord->application->commands->save(
    $discord->application->commands->create(CommandBuilder::new()
        ->setName('setup')
        ->setDescription('Set up verify panel')
        ->addOption((new Option($discord))
          ->setName('role')
          ->setDescription('Role granted to verified user')
          ->setType(Option::ROLE)
          ->setRequired(true)
        )
        ->addOption((new Option($discord))
          ->setName('lang')
          ->setDescription('en | ja')
          ->setType(Option::STRING)
          ->addChoice((new Choice($discord))->setName("English")->setValue("en"))
          ->addChoice((new Choice($discord))->setName("日本語")->setValue("ja"))
        )->toArray()
    )
  );
});

$client->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
    global $lang;
    if ($interaction->type == 3) {
      if (str_starts_with($interaction->data->custom_id, "verify")) {
        $lang_name = str_replace("verify_", "", $interaction->data->custom_id);
        if ($interaction->data->custom_id == $lang_name) $lang_name = "en";
        
        $token = md5($interaction->user->id . "-" . $interaction->guild->id);
        $filename = "data/token/" . $token . '.json';
        if (file_exists($filename)) {
          $time = intval(json_decode(file_get_contents($filename), true)['time']);
          if ($time + 180 >= time()) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent(str_replace("%time%", (180 - intval(time() - $time)), $lang[$lang_name]['cooldown'])), true);
            return;
          }
        }
        file_put_contents($filename, json_encode(array(
          'user' => $interaction->user->id,
          'guild' => $interaction->guild->id,
          'time' => time(),
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed(["title" => $lang[$lang_name]['panel_text2'], "description" => "https://verify.pkom.ml/?token=" . $token . "&lang=" . $lang_name]), true);
      }
    }
});

$client->listenCommand('setup', function (Interaction $interaction) {
  global $client, $lang;

  $lang_name = "en";
  $lang_name = $interaction->data->options["lang"]->value;
  $button = Button::new(Button::STYLE_PRIMARY)->setLabel($lang[$lang_name]['verify'])->setCustomId("verify_" . $lang_name);
  
  $permissions = $interaction->member->getPermissions();
  if ($permissions['administrator'] == false) {
    $interaction->respondWithMessage(MessageBuilder::new()->setContent($lang[$lang_name]['notadmin_warming']), true);
    return;
  }
  
  $embed = [
    "title" => $lang[$lang_name]['verification'], 
    "description" => $lang[$lang_name]['panel_text'],
  ];
  $interaction->channel->sendMessage(MessageBuilder::new()->addEmbed($embed)->addComponent(ActionRow::new()->addComponent($button)));
  $interaction->respondWithMessage(MessageBuilder::new()->setContent($lang[$lang_name]['placed']), true);

  $filename = "data/guild/" . $interaction->guild->id . '.json';
  $data = array();
  if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
  }

  $data["id"] = $interaction->guild->id;
  $data["role"] = $interaction->data->resolved->roles->first()->id;
  
  file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
});

$client->run();