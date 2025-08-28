<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Process Telegram webhook
$input = file_get_contents('php://input');
error_log("Webhook triggered. Input: $input");
$data = json_decode($input, true);
$db_host=getenv('db_host');
$db_port=getenv('db_port');
$db_name=getenv('db_name');
$db_user=getenv('db_user');
$db_pass=getenv('db_pass');

if (isset($data['message'])) {
    $chat_id = $data['message']['chat']['id'];
    $first_name=$data['message']['from']['first_name']
    if (isset($data['message']['text']) && strcasecmp($data['message']['text'], '/start') === 0) {
        $keyboard = [
            'keyboard' => [[['text' => 'Share Phone Number', 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];
        $response =sendMessage($chat_id, "Please share your phone number:", $keyboard);
    }
    if (isset($data['message']['contact'])) {
        $phone = $data['message']['contact']['phone_number'];        
        store_in_db($db_host,$db_port,$db_name,$db_user,$db_pass,$chat_id,$phone,$first_name);

        
    }
}

function sendMessage($chat_id, $text, $keyboard = null) {
    $token = getenv('bot_token');
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $post = ['chat_id' => $chat_id, 'text' => $text];
    if ($keyboard) {
        $post['reply_markup'] = json_encode($keyboard);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function store_in_db($db_host,$db_port,$db_name,$db_user,$db_pass,$chat_id,$phone,$first_name){

        try{

            $conn = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
            $conn ->setAttribute(PDO::ATTR_ERRMODE, PDO:: ERRMODE_EXCEPTION);
            $stmt =$conn->prepare("INSERT INTO users (phone_number, chat_id,first_name) VALUES (:phone_number, :chat_id, :first_name)");
            $stmt->execute([':phone_number'=>$phone,':chat_id'=>$chat_id,':first_name'=>$first_name]);

            sendMessage($chat_id, "Dear! $first_name Your number is: $phone You Have Sucessfully Registered On Bingo Bay");
        }
        catch (PDOException $e){
            sendMessage($chat_id, "Error:"  . $e->getMessage());
        }
}
?>