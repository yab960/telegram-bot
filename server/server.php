<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require __DIR__ . '/vendor/autoload.php';
include('../conn.php');

class bingoGame implements MessageComponentInterface{
    protected $clients;
    protected $loop;
    protected $cards;
    protected $conn_db;
    public function __construct($loop,$conn_db){
        $this->clients = new \SplObjectStorage;
        $this->loop =$loop;
        $this->conn_db = $conn_db;
        $this ->cards = $this->generate_bingo_cards(100);
        echo "Bingo Server Started";
    }

    public function onOpen(ConnectionInterface $conn){
        $this->clients->attach($conn);
        echo "New Player:\n";
        $conn->send(json_encode($this->cards));
    }

    public function onMessage(ConnectionInterface $from, $msg){
        $data =json_decode($msg,true);
        $action = $data['action'];
        $index = $data['card'];
        $player_number =$data['player_number'];
        if($action == 'choose_card' && $index !=null){
            $this->cards[$index]['status']='taken';
            $this->store_card($player_number,$index,$this->conn_db);
            $confirmationMessage = [
                'action' => 'card_accepted',
                'cardIndex' => $index,
                'player_number'=>$player_number,
                'message' => 'Your card choice has been accepted.'
            ];
            $from->send(json_encode($confirmationMessage));
            $broadcastMessage=[
                'action'=>'card_taken',
                'card_index'=>$index
            ];
            foreach($this->clients as $client){
                $client->send(json_encode($broadcastMessage));
                
            }
        }

        
    }
    public function onClose(ConnectionInterface $conn){
        
    }
    public function onError(ConnectionInterface $conn, \Exception $e){
        
    }

    function generateSingleCard($bingoRanges){
        $pack = [];
        $card=[];
        foreach ($bingoRanges as $coloumn => $range){
            $numbers = array_splice($range, 0,5);
            shuffle($numbers);
            $card[$coloumn]=$numbers;
        }

        $card['N'][2] ='Free';
        $pack['card']=$card;
        $pack['status']="available";
        return $pack;
    }

    function generate_bingo_cards($numCards=50){
        $cards=[];

        $bingoRanges=[
            'B' => range(1, 15),
            'I' => range(16, 30),
            'N' => range(31, 45),
            'G' => range(46, 60),
            'O' => range(61, 75)
        ];

        for ($i =0; $i <$numCards; $i++){
            $card =$this-> generateSingleCard($bingoRanges);
            $cards[]=$card;
        }
        return $cards;
    }

    private function store_card($player_number,$index,$conn){
        try{
            $stmt = $conn->prepare('INSERT INTO public.user_card (phone_number,card_index) VALUES(:phone_number,:card_index)');
            $stmt->bindParam(':phone_number',$player_number,PDO::PARAM_STR);
            $stmt->bindParam(':card_index',$index,PDO::PARAM_STR);
            $result = $stmt->execute();
            // echo"stored";
            return $result ?true : false;
        } catch(PDOException $e){
            echo "Error" .$e->getMessage();
            return false;
        }
    }

}


$loop = React\EventLoop\Factory:: create();

$port = 8080;
$webSock = new React\Socket\SocketServer("0.0.0.0:$port",[], $loop);
$server = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            new bingoGame($loop,$conn_db)
        )
    ),
    $webSock,
    $loop
);
$server->run();


?>