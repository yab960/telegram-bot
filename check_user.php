<?php

$data = json_decode(file_get_contents('php://input'), true);
$chat_id = $data['chat_id'] ?? null;
$db_host=getenv('db_host');
$db_port=getenv('db_port');
$db_name=getenv('db_name');
$db_user=getenv('db_user');
$db_pass=getenv('db_pass');

$conn = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
$conn ->setAttribute(PDO::ATTR_ERRMODE, PDO:: ERRMODE_EXCEPTION);
$response = ['registered' => false];

if($chat_id){
    $stmt=$conn->prepare("SELECT first_name FROM users WHERE chat_id = :chat_id");
    $stmt ->execute([':chat_id'=>$chat_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result){
        $response['registered'] = true;
        $response['first_name'] =$result['first_name'];
    }
}
header('Content-Type: application/json');
echo json_encode($response);
?>