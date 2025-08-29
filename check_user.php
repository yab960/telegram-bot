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
    $stmt=$conn->prepare("SELECT * FROM users WHERE chat_id = :chat_id");
    $stmt ->execute([':chat_id'=>$chat_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result){
        $response['registered'] = true;
        $response['balance']=get_balance($conn,$result['phone_number']);
        $response['first_name'] =$result['first_name'];
    }
}
header('Content-Type: application/json');
echo json_encode($response);

function get_balance($conn,$phone_number){
    $stmt=$conn->prepare("SELECT amount FROM balance WHERE user_id = :user_id");
    $stmt ->execute([':user_id'=>$phone_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result){
        $amount =$result['amount'];
    }
    else{
        $wallet = create_wallet($conn,$phone_number);
        if(wallet){
            $amount=0;
        }
        else{
            $amount = 'error';
        }
    }
    return $amount;

}
function create_wallet($conn,$phone_number){
    $now = date('Y-m-d H:i:s');
    $stmt=$conn->prepare("INSERT INTO balance (user_id,amount,updated_at) Values(:phone_number,0,:now)");
        if($stmt ->execute([':phone_number'=>$phone_number,':now'=>$now])){
            return true;
        }
        else{
            return false;
        }

}
?>