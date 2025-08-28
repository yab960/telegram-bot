<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$input = file_get_contents('php://input');
error_log("Webhook triggered. Input: $input");
$data = json_decode($input, true);
error_log("Decoded data: " . print_r($data, true));

if (isset($data['message'])) {
    error_log("Message received: " . print_r($data['message'], true));
    $chat_id = $data['message']['chat']['id'];
    if (isset($data['message']['text']) && strcasecmp($data['message']['text'], '/start') === 0) {
        error_log("Processing /start command for chat_id: $chat_id");
        $keyboard = [
            'keyboard' => [[['text' => 'Share Phone Number', 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];
        $response = sendMessage($chat_id, "Please share your phone number:", $keyboard);
        error_log("SendMessage response: " . print_r($response, true));
    }
    if (isset($data['message']['contact'])) {
        $phone = $data['message']['contact']['phone_number'];
        error_log("Contact received: $phone");
        sendMessage($chat_id, "Thanks! Your number is: $phone");
    }
}

function sendMessage($chat_id, $text, $keyboard = null) {
    $token = '8386264013:AAEHYs4d-9u8M2mfiYsNMQA8GuLmbBWG8Qc';
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
    if ($result === false) {
        error_log("CURL error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($result, true);
}
?>