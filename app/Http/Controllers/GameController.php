<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Status;

class GameController extends Controller
{
    public function start()
    {
        //create a unique id;;
        $gameId = uniqid();

        //create current game status and store it in db
        $status = new Status();
        $status->gameId = $gameId;
        $status->save();

        $gameUrl = 'http://'.$_SERVER['HTTP_HOST'] . '/api/game/play/'.$gameId.'?answer=';

 
        $instructions ="Use the url generated below to play the game.".
                "The game usually starts counting at 1 and ends at 100.". 
                "Use the rules of FizzBuzz game. i.e Start with 1, and continue by entering the next number in the sequence.". 
                "However, if the next number is divisible by 3, enter 'fizz' instead. If divisible by 5, enter 'buzz'.".
                "If it is divisible by both 3 and 5, enter 'fizzbuzz'. Entering the wrong response ends the game.".
                "Append your answer at the end of your generated game_url eg. $gameUrl . 1 assuming your response is 1" ;

                
        return response()->json([
            'message'=>'Game started.',
            'instructions'=>$instructions,
            'game_url'=>$gameUrl
        ], 200);
    }

    public function play(Request $request,$gameId)
    {
        $gameStatus = Status::where('gameId', $gameId)->first();

        $startNewGameUrl ='http://'.$_SERVER['HTTP_HOST'] . '/api/game/start';

        //if the gameStatus is null, the gameId is invalid, return
        if($gameStatus == null){
            return response()->json([
                'message'=>'The game session is invalid. Use the url below to start a new game session',
                'url'=> $startNewGameUrl
            ]);
        }

        $answer = strtolower($request->answer);
        $gameUrl = 'http://'.$_SERVER['HTTP_HOST'] . '/api/game/play/'.$gameStatus->gameId.'?answer=';
       
        
        //retrieve previousNumber from database 
        $previousNumber = $gameStatus->previousNumber;

        $correctResponse = $this->calculateExpectedResponse($previousNumber);

       
        if($answer == $correctResponse ){
            //succeeded.
            //if previousNumber++ == 100 -->end game -->delete the game status from db value from db
            if($previousNumber++ == 100){
                $gameStatus->delete();
                return response()->json([
                    'message'=>'Congrats. Game Finished. You have won. Use the start game url below to start a new game',
                    'new_game_url'=> $startNewGameUrl
                ],200);
            }
            else {// the user can continue with the game
              //update new value in db
                $gameStatus->previousNumber+=1;
                $gameStatus->save();

                return response()->json([
                    'message'=>'Correct answer. Continue the game by entering the next response in game url',
                    'game_url'=>$gameUrl
                ],200);
            }
        }else {
            //failed game over --> delete  the game status from db
            $gameStatus->delete();
            return response()->json([
                'message'=>'Whoops! ;( . Sorry, you entered the wrong response. Game Over. Use the url below to start a new game',
                'new_game_url'=>$startNewGameUrl
            ],200);
        }

    }

    private function calculateExpectedResponse($prevNumber)
    {

        $currentNumber = $prevNumber + 1;

        
        if($currentNumber % 3 == 0 && $currentNumber % 5 == 0){
            return "fizzbuzz";
        }else if ($currentNumber % 3 == 0){
            return "fizz";
        }else if ($currentNumber % 5 == 0){
            return "buzz";
        }else {
            return $currentNumber;
        }
        
    }
}
