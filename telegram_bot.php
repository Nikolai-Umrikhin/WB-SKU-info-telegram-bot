<?php

require_once('Controller.php');
require_once('Database.php');

$data = json_decode(file_get_contents('php://input'), TRUE);
//create log file
file_put_contents('log.txt', '$data: '.print_r($data, 1)."\n", FILE_APPEND);

define('TOKEN', 'TELEGRAM API KEY');
$data   = $data['callback_query'] ? $data['callback_query'] : $data['message'];
$method    = 'sendMessage';

$params = [
	'telegram_id' => $data['from']['id'],
	'username' => $data['from']['username'],
	'first_name' => $data['from']['first_name'],
	'status' => NULL
];

$controller = new MainController();
$DataBase = new DataBase();

$message  = mb_strtolower(($data['text'] ? $data['text'] : $data['data']),'utf-8');

$statusValue = $DataBase->getValue('`status`', 'telegram_users', "`telegram_id` = '" . $data['from']['id'] . "'");

if($statusValue['status'] == 'CheckSKU'){
	switch($message){
		default:
		$params['status'] = NULL;
		$DataBase->QuerryStatusUpdate($params); 
		$send_data['text'] = 'Ожидайте...';
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
		$prodInfo = $controller->getInfo($message);
		$createCSV= $controller->createCSV($prodInfo, $data);
		$controller->sendDocument($data['chat']['id'], $createCSV);
		$send_data= ['text' => 'Отчет готов!'];
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
	}
}

else{
	switch($message){
		case '/start':
		if(!$DataBase->UserSearchRow('`telegram_users`', '\'' . $data['from']['id'] . '\'')){
			$DataBase->InstertRow($params);
		}
		$params['status'] = NULL;
		$DataBase->QuerryStatusUpdate($params);
		$send_data = [

			'text' => 'Приветствую! Данный бот поможет вам собрать информацию о товарах Wildberries.',
			'reply_markup'  => [
				'resize_keyboard' => true,
				'one_time_keyboard'  => true,
				'keyboard' => [
					[
						['text' => 'проверить артикулы'],
						['text' => 'информация о боте'],
					]
				]
			]
		];
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
		break;

		case 'проверить артикулы':
		$params['status'] = 'CheckSKU';
		$DataBase->QuerryStatusUpdate($params);
		$send_data = ['text' => 'Введите артикулы товаров через запятую (Пример: 53840226,113251542 )'];
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
		break;

		case 'информация о боте':
		$send_data = ['text' => 'Данный бот позволяет получать информацию об артикулах товаров Wildberries. '];
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
		break;

		default:
		$send_data = [
			'text' => 'Команда не распознана.',
			'reply_markup'  => [
				'resize_keyboard' => true,
				'keyboard' => [
					[
						['text' => 'проверить артикулы'],
						['text' => 'информация о боте'],
					]
				]
			]
		];
		$send_data['chat_id'] = $data['chat']['id'];
		$controller->sendTelegram($method, $send_data);
	}
}

