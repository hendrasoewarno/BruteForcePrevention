<?php
/*
ketik: @Botfather
pilih: /newbot

pilih bot name: Hendrasoewarnobot
pilih username: Hendrasoewarnobot

Anda diberi bot token, simpan bot token anda.

Siapkan URL dan endpoint yang akan dipanggil oleh bot
https://api.telegram.org/bot{bot_token}/setWebhook?url={your_server_url}

contoh

https://api.telegram.org/bot1863288706:AAGC0d01Gv0Ag55p7J65PHPOwggTRU-----/setWebhook?url=https://ec2-3-15-196-245.us-east-2.compute.amazonaws.com/Hendrasoewarnobot/webhook.php

buat sertifikat:
https://www.selfsignedcertificate.com/

Konversi cert menjadi pem
openssl x509 -in cert.cer -out cert.pem


curl -F "url=https://ec2-3-15-196-245.us-east-2.compute.amazonaws.com/Hendrasoewarnobot/webhook.php" -F "certificate=@/etc/ssl/myCerts/72576561_ec2-3-15-196-245.us-east-2.compute.amazonaws.com.pem" https://api.telegram.org/bot1863288706:AAGC0d01Gv0Ag55p7J65PHPOwggTRU-----/setWebhook

https://api.telegram.org/bot1863288706:AAGC0d01Gv0Ag55p7J65PHPOwggTRU-----/getWebhookInfo
*/
include_once "framework/library.php";
include_once "TrialSummary.php";

define('UNIT_TEST', false);
define('BOT_TOKEN', 'yourtoken');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define('MESSAGE', 0);
define('CALLBACK', 1);
define('MEMBER', array(568577002)); //put your chatid here

function isMember($chatId) {
	return in_array($chatId, MEMBER);
}

class Bot {
	private $con;
	private $obj;
	private $objType;
	private $fromId;
	private $chatId;
	private $response;
	private $keyboard="";
	private $parseMode="HTML";
	
	function __construct($body) {
		logDebug($body);
		$this->obj = json_decode($body); //object
		//message atau callback
		if (isset($this->obj->message)) {
			$this->objType = MESSAGE;
			$this->fromId = $this->obj->message->from->id;
		}
		else {
			$this->objType = CALLBACK;
			$this->fromId = $this->obj->callback_query->from->id;
		}
	}
	
	function getFormId() {
		return $this->formId;
	}
	
	//User Message adalah perintah yang diawali dengan / (seperti /start)
	function processUserMessage(){
		$ret = "";
		$this->chatId = $this->obj->message->chat->id;	
		$date = $this->obj->message->date;
		$text = $this->obj->message->text;
		if (isMember($this->chatId)) {
			if (strpos($text, '/start')===0) {
				$this->response = "Selamat datang ke cdn0001bot.";
				//ini contoh kalau mau buat keyboard
				$keyboard = array(
					"keyboard" => array(
						array(
							array("text" => "By Rule", "callback_data" => "mrule"),
							array("text" => "By Name", "callback_data" => "mname"),
							array("text" => "By Country", "callback_data" => "mcountry"),
						)
					),
					"resize_keyboard" => true,
					"one_time_keyboard" => false
				);
				$this->keyboard = json_encode($keyboard, true);
			} elseif (strpos($text, '/reg')===0) {
				$this->response = "belum diimplementasikan";
			} else {
				$this->response = "perintah tidak dikenali!";
			}
		} else {
			$this->response = "Maaf hanya untuk member.";
		}
	}

	//User Response adalah balasan dari user tanpa diawali /
	function processUserCallback() {
		$ret = "";
		$this->chatId = $this->obj->callback_query->message->chat->id;
		$date = $this->obj->callback_query->message->date;
		$data = $this->obj->callback_query->data;
		if (isMember($this->chatId)) {		
			if ($data=="mrule") {
				$this->response = "pilihan untuk rule";
				$keyboard = array(
					"keyboard" => array(
						array(
							array("text" => "Today", "callback_data" => "rule".date("Y-m-d") . "to" . date("Y-m-d")),
							array("text" => "MTD", "callback_data" => "rule".date("Y-m-01") . "to" . date("Y-m-t")),
							array("text" => "YTD", "callback_data" => "rule".date("Y-01-01") . "to" . date("Y-12-31"))
						)
					),
					"resize_keyboard" => true,
					"one_time_keyboard" => false					
				);
				$this->keyboard = json_encode($keyboard, true);
			} else if ($data=="mname") {
				$this->response = "pilihan untuk name";
				$keyboard = array(
					"keyboard" => array(
						array(
							array("text" => "Today", "callback_data" => "name".date("Y-m-d") . "to" . date("Y-m-d")),
							array("text" => "MTD", "callback_data" => "name".date("Y-m-01") . "to" . date("Y-m-t")),
							array("text" => "YTD", "callback_data" => "name".date("Y-01-01") . "to" . date("Y-12-31")),
						)
					),
					"resize_keyboard" => true,
					"one_time_keyboard" => false
				);
				$this->keyboard = json_encode($keyboard, true);				
			} else if ($data=="mcountry") {
				$this->response = "pilihan untuk country";
				$keyboard = array(
					"keyboard" => array(
						array(
							array("text" => "Today", "callback_data" => "country".date("Y-m-d") . "to" . date("Y-m-d")),
							array("text" => "MTD", "callback_data" => "country".date("Y-m-01") . "to" . date("Y-m-t")),
							array("text" => "YTD", "callback_data" => "country".date("Y-01-01") . "to" . date("Y-12-31")),
						)
					),
					"resize_keyboard" => true,
					"one_time_keyboard" => false					
				);
				$this->keyboard = json_encode($keyboard, true);				
			} else if (startsWith($data, "rule")) {
				$this->response = "```\n" . getTrialSummary(substr($data,4), "rule") . "\n```";
				$this->parseMode = "Markdown";
			} else if (startsWith($data, "name")) {
				$this->response = "```\n" . getTrialSummary(substr($data,4), "name") . "\n```";
			} else if (startsWith($data, "country")) {
				$this->response = "```\n" . getTrialSummary(substr($data,6), "country") . "\n```";
			} else {
				$this->response = "callback tidak dikenali!" . $data;
			}
		} else {
			$this->response = "Maaf hanya untuk member.";			
		}
	}
		
	function replyToSender() {
		if ($this->keyboard=="")
			$sendto = API_URL . "sendmessage?chat_id=" . $this->chatId . "&text=" . urlencode($this->response) . "&parse_mode=" . $this->parseMode;		
		else
			$sendto = API_URL . "sendmessage?chat_id=" . $this->chatId . "&text=" . urlencode($this->response) . "&parse_mode=" . $this->parseMode . "&reply_markup=" . urlencode($this->keyboard);
		logDebug($sendto);
		file_get_contents($sendto);
	}
	
	function processRequest() {
		if ($this->objType==MESSAGE)
			$this->processUserMessage();
		else
			$this->processUserCallback();
		$this->replyToSender();
	}
}

// Entry Point
if (!UNIT_TEST) {
	try {
		$body = file_get_contents("php://input");
		if (strlen($body)>0) {
			$bot = new Bot($body);
			//check user berdasarkan $bot->getFormId()
			$bot->processRequest();
		}
		else
			echo "No payload";
	} catch (Execption $e) {
		logDebug($e->getMessage());
	}
} else {

//unit test
/*
$body = <<<EOD
{
	"update_id":250400716,
	"message":{
		"message_id":108,
		"from":{
			"id":568577002,
			"is_bot":null,
			"first_name":"Bob",
			"last_name":"Bob",
			"language_code":"en-us"
		},
		"chat": {
			"id":568577002,
			"first_name":"Bob",
			"last_name":"Bob",
			"type":"private"
		},
		"date":1538584262,
		"text":"/start"
	}
}
EOD;
*/
$body=<<<EOD
{
    "update_id":250400717,
    "callback_query":{
        "id":2442019628980761722,
        "from":{
            "id":568577002,
            "is_bot":0,
            "first_name":"Bob",
            "last_name":"Bob",
            "language_code":"en-us"
        },
        "message":{
            "message_id":109,
            "from":{
                "id":465801377,
                "is_bot":1,
                "first_name":"Hendrasoewarnobot",
                "username":"Hendrasoewarnobot"
            },
            "chat":{
                "id":568577002,
                "first_name":"Bob",
                "last_name":"Bob",
                "type":"private"
            },
            "date":1538584264,
            "text":"Hello"
        },
        "chat_instance":2365774229782843677,
        "data":"rule1990-01-01to2099-12-31"
    }
}
EOD;
$bot = new Bot($body);
//check user berdasarkan $bot->getFormId()
$bot->processRequest();
}
?>
