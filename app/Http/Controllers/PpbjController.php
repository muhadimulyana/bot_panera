<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PpbjController extends Controller
{

    public function app()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        
        $chatId = $update["message"]["chat"]["id"];
        $chatName = $update["message"]["chat"]["first_name"];
        $message = $update["message"]["text"];

        try {

            $checkId = DB::table("daftar_id_telegram")->getWhere("ID_CHAT = ? AND AKTIF = 1", [$chatId])->first();
            
            if($checkId) {

                $this->sendMessage([
                    "chat_id" => $chatId,
                    "parse_mode" => "HTML"
                ],  "Akun anda sudah terdaftar, anda akan menerima notifikasi approval PPBJ, apabila ada permintaan", );

            } else {

                $this->sendMessage([
                    "chat_id" => $chatId,
                    "parse_mode" => "HTML"
                ],  "Akun anda belum terdaftar, silahkan daftar terlebih dahulu di bot dibawah ini", );

            }

        } catch (QueryException $e) {

            $this->sendMessage([
                "chat_id" => $chatId,
                "parse_mode" => "HTML"
            ],  "Terjadi Kesalahan [" . $e->getCode() . "]");

        }

    }

    public function sendMessage($content, $text, $keyboard = [])
    {
        $path = "https://api.telegram.org/bot2041978972:AAHUNfNsjaiv1JGHUfqmTfWk0nkXfpnOLJ4";
        if($keyboard) {
            $keyboard = json_encode($keyboard);
            file_get_contents($path.'/sendmessage?text=' . $text . '&reply_markup=' . $keyboard . '&' . http_build_query($content));
        } else {
            file_get_contents($path.'/sendmessage?text=' . $text . '&' . http_build_query($content));
        }
    }

    public function test()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        
        $chatId = $update["message"]["chat"]["id"];
       

        $keyboard = [
            "inline_keyboard" =>  [
                [
                    [
                        "text" => "Button 1",
                        "url" => "https://google.com"
                    ],
                    [
                        "text" => "Button 2",
                        "url" => "https://facebook.com"
                    ]
                ]
            ]
        ];

        $this->sendMessage([
            "chat_id" => $chatId,
            "parse_mode" => "HTML"
        ], "Hellow", $keyboard);


    }


}