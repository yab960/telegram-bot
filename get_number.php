<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Process Telegram webhook
$token = getenv('GITHUB_TOKEN');
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$db_host='dpg-d2ncplvdiees73cg2l00-a';
$db_port='5432';
$db_name='bingodb_ln7t';
$db_user='bingodb_ln7t_user';
$db_pass='REUKeK5sT9mzpYPzwMXt0qJBlBrvoTr4';



if (isset($data['message'])) {
    $chat_id = $data['message']['chat']['id'];
    if (isset($data['message']['text']) && strcasecmp($data['message']['text'], '/start') === 0) {
        $keyboard = [
            'keyboard' => [[['text' => 'Share Phone Number', 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];
        sendMessage($chat_id, "Please share your phone number:", $keyboard);
    }
    if (isset($data['message']['contact'])) {
        $phone = $data['message']['contact']['phone_number'];
        // For free tier, store phone numbers in a database or external service (not implemented here)
        

        try{
            $conn = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
            $conn ->setAttribute(PDO::ATTR_ERRMODE, PDO:: ERRMODE_EXCEPTION);
            $stmt =$conn->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
            $stmt->execute([':name'=>'yab',':email'=>'email']);

            sendMessage($chat_id, "Thanks! Your number is: $phone");
        }
        catch (PDOException $e){
            sendMessage($chat_id, "Error:"  . $e->getMessage());
        }
        
    }
}

function sendMessage($chat_id, $text, $keyboard = null) {
    $token = '8386264013:AAGhiykMS6Yc0xs8PeKEZDf_RurkUWomeyo';
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
?>