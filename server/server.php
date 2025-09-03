<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require __DIR__ . '/vendor/autoload.php';
include('../conn.php');
enum state{
        case choosing;
        case playing;
        case pause;
        case bingo;
        };

class bingoGame implements MessageComponentInterface{
    protected $clients;
    protected $choosing_count_down;
    protected $loop;
    protected $cards;
    protected $players;
    protected $conn_db;
    protected $state;
    protected $draw;
    public function __construct($loop,$conn_db){
        $this->clients = new \SplObjectStorage;
        $this->loop =$loop;
        $this->conn_db = $conn_db;
        $this ->cards = $this->generate_bingo_cards(10);
        $this ->players=[];
        $this ->state= state::choosing;
        $this ->choosing_count_down=35;
        $this ->draw = [];
        echo "Bingo Server Started\n";
        
        $this->loop->addPeriodicTimer(1, function ($timer){
            $this->choosing_count_down--;
            if($this->choosing_count_down <0){
                echo"time is up for choosing\n";
                $this->state=$this->state::playing;
                $this->loop->cancelTimer($timer);
            }

        
        });

        $this->gameTimer = $this->loop->addPeriodicTimer(1, function ($timer){
            $this->choosing_count_down--;
            if($this->state==$this->state::playing && count($this->draw)<75){
                $number =$this->geenerate_number();
                foreach($this->clients as $client){
                    $client->send(json_encode([
                        'action'=>'draw',
                        'number'=>$number
                    ]));
                }
                $this->draw[]=$number;
                echo "$number and lenght is". count($this->draw)."\n";
            }
            else if(count($this->draw)>70){
                echo"fifished";
                $this->loop->cancelTimer($this->gameTimer);
            }
        });
    }

    public function onOpen(ConnectionInterface $conn){
        $this->clients->attach($conn);
        echo "New Player:\n";
        if($this->state == state::choosing){
            $conn->send(json_encode($this->cards));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg){
        $data =json_decode($msg,true);
        $action = $data['action'];

        if($action == 'get_card'){
            $number=$data['number'];
            echo "Player with number: $number is asking for their card\n";
            $index = $this->player[$number];
            echo"the players card index is $index[0]\n";
            $player_card = $this->cards[$index[0]];
            $from->send(json_encode(
                [   'action'=>'here_is_your_card',
                    'card'=>$player_card
                ]
            ));

        }
        else if($action=="win"){
            $index = $data['card'];
            $player_number =$data['player_number'];
       
            
            $this->state == $this->state::bingo;
        }
        else if($action=='bingo'){
            $player_number=$data['player_number'];
            $marked_number=$data['marked_number'];
            $index = $this->player[$player_number];
            $card=$this->cards[$index[0]];
            if($this->check_bingo($marked_number,$this->draw,$card['card'])){
                $this->loop->cancelTimer($this->gameTimer);
                echo "Game timer stopped.\nWe Have A winner\n";
            }

        }
        else if($action == 'choose_card'){
            $index = $data['card'];
            $player_number =$data['player_number'];
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

    private function generate_winning_pattern($card){
        $patterns=[];
        $patterns[]=$card['B'];
        $patterns[]=$card['I'];
        $patterns[]=$card['N'];
        $patterns[]=$card['G'];
        $patterns[]=$card['O'];

        for($col =0; $col<5; $col++){
            $verticalPattern = [
                $card['B'][$col],
                $card['I'][$col],
                $card['N'][$col],
                $card['G'][$col],
                $card['O'][$col]
            ];
            $patterns[]=$verticalPattern;
        }
        $diagonal1=[
            $card['B'][0],
            $card['I'][1],
            $card['N'][2],
            $card['G'][3],
            $card['O'][4]
        ];
        $patterns[]=$diagonal1;

        $diagonal2=[
            $card['B'][4],
            $card['I'][3],
            $card['N'][2],
            $card['G'][1],
            $card['O'][0],
        ];
        $patterns[]=$diagonal2;

        return $patterns;
    }

    private function store_card($player_number,$index,$conn){
        try{
            $stmt = $conn->prepare('INSERT INTO public.user_card (phone_number,card_index) VALUES(:phone_number,:card_index)');
            $stmt->bindParam(':phone_number',$player_number,PDO::PARAM_STR);
            $stmt->bindParam(':card_index',$index,PDO::PARAM_STR);
            $result = $stmt->execute();
            $this->player[$player_number][]=$index;
            foreach($this->player as $pid=> $indxes)
            {
                echo $pid;
                foreach($indxes as $idx){
                    echo ": $idx\n";
                }
            }
            return $result ?true : false;
        } catch(PDOException $e){
            echo "Error" .$e->getMessage();
            return false;
        }
    }

    private function geenerate_number(){
        do{

            $random =rand(1,75);
        }
        while(in_array($random,$this->draw));
        
        return $random;

    }

    private function check_bingo($marked_number,$draw,$card){
        $winning_pattern=$this->generate_winning_pattern($card);

        foreach($winning_pattern as $pattern){
            $isWinner = true;

            foreach($pattern as $number){
                if(!in_array($number,$marked_number))
                {
                    $isWinner=false;
                    break;
                }
            }
            if($isWinner){
                foreach($pattern as $number){
                    if($number !== 'Free' && !in_array($number,$draw)){
                        echo"cheat detected\n";
                        return false;
                    }
                }
                $this->reset();
                return true;
            }
        }
        return false;

    }

    private function reset(){
        echo "Reseting Game \n";
        $this->draw=[];
        $this->state=GameState::choosing;
        $this->choosing_count_down = 35;
        $this->cards =$this->generate_bingo_cards(10);
        foreach($this->clients as $client){
            $client->send(json_encode([
                'action'=>'new_round',
                'cards'=>$this->cards
            ]));
        }
        $this->startChoosingTimer();

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