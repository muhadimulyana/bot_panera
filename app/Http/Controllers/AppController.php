<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    public function app()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        
        $chatId = $update["message"]["chat"]["id"];
        $chatName = $update["message"]["chat"]["first_name"];
        $message = $update["message"]["text"];

        try {

            $layanan = DB::table("layanan")->whereRaw("ID_CHAT = ? AND AKTIF = 1", [$chatId])->first();

            if($layanan) {
                //Layanan sedang aktif
                DB::table("layanan")->where('ID_CHAT', $chatId)->update([
                    "ID_CHAT" => $chatId,
                    "UPDATED_AT" => date("Y-m-d H:i:s")
                ]);
    
                if($layanan->USERNAME === NULL) {
    
                    $message = explode(" ", $message);
                    if(count($message) === 2) {
    
                        $user = DB::table("hrd.viewkaryawanprofile as a")->join("user_management.user_right as b", "a.Nik", "=", "b.nik")->whereRaw("b.User_Name = ? AND b.Pass = ? AND a.Aktif = 1", [$message[0], $message[1]])->first();
    
                        if($user) {
    
                            DB::table("layanan")->where('ID_CHAT', $chatId)->update([
                                "ID_CHAT" => $chatId,
                                "USERNAME" => $message[0],
                                "PIN" => $user->pin,
                                "UPDATED_AT" => date("Y-m-d H:i:s")
                            ]);
    
                            $text = "Halo <strong>$user->Nama</strong> 😃 Berikut ini daftar menu yang dapat digunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                            $keyboard = [
                                ['/daftar','/selesai'],
                            ];
                
                            $markupKeyboard = [
                                "keyboard" => $keyboard,
                                'resize_keyboard' => true, 
                                'one_time_keyboard' => true
                            ];
    
                            $this->sendMessage([
                                "chat_id" => $chatId,
                                "parse_mode" => "HTML"
                            ], urlencode($text), $markupKeyboard);
    
                        } else {
    
                            $this->sendMessage([
                                "chat_id" => $chatId,
                                "parse_mode" => "HTML"
                            ], "Username atau password salah, coba lagi");
    
                        }
    
                    } else {
    
                        $this->sendMessage([
                            "chat_id" => $chatId,
                            "parse_mode" => "HTML"
                        ], "Format masukkan salah, pastikan format yang anda masukkan sudah benar");
                        sleep(1);
                        $this->sendMessage([
                            "chat_id" => $chatId,
                            "parse_mode" => "HTML"
                        ], "<i>cth: mamulyana Loco88-55</i>");
    
                    }
                    
                } else {
    
                    $command = [
                        "/daftar",
                        "/selesai",
                        "/batal"
                    ];
    
                    $layananAktif = DB::table("layanan as a")->join("layanan_detail as b", "a.ID_LAYANAN", "=", "b.ID_LAYANAN")->whereRaw("a.ID_CHAT = ? AND a.AKTIF = 1 AND b.AKTIF = 1", [$chatId])->first();
    
                    if($layananAktif) {
    
                        if($layananAktif->COMMAND == "/daftar") {
                            
                            if($message == "/selesai") {
    
                                DB::table("layanan")->where('ID_CHAT', $chatId)->update([
                                    "ID_CHAT" => $chatId,
                                    "AKTIF" => 0,
                                    "UPDATED_AT" => date("Y-m-d H:i:s")
                                ]);
    
                                $this->sendMessage([
                                    "chat_id" => $chatId,
                                    "parse_mode" => "HTML"
                                ], "Terimakasih telah menggunakan layanan ini 😊");
        
                            } elseif($message == "/batal") {
                                
                                DB::table("layanan_detail")->where('ID_LAYANAN', $layanan->ID_LAYANAN)->update([
                                    "AKTIF" => 0,
                                ]);
        
                                $text = "Daftar menu: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                                $keyboard = [
                                    ['/daftar','/selesai'],
                                ];
                    
                                $markupKeyboard = [
                                    "keyboard" => $keyboard,
                                    'resize_keyboard' => true, 
                                    'one_time_keyboard' => true
                                ];
    
                                $this->sendMessage([
                                    "chat_id" => $chatId,
                                    "parse_mode" => "HTML"
                                ], urlencode($text), $markupKeyboard);
                                
                            } else {
    
                                if($message == $layanan->PIN) {
    
                                    DB::table("daftar_id_telegram")->insert([
                                        "ID_CHAT" => $chatId,
                                        "USERNAME" => $layanan->USERNAME,
                                        "PROFILE_NAME" => $chatName,
                                        "AKTIF" => 1,
                                        "CREATED_AT" => date("Y-m-d H:i:s")
                                    ]);
        
                                    DB::table("layanan_detail")->where('ID_LAYANAN', $layanan->ID_LAYANAN)->update([
                                        "AKTIF" => 0,
                                    ]);

                                    $bots = DB::table("bot")->whereRaw("KODE <> ?", ["general"])->get();

                                    $keyboard = [ 'inline_keyboard' => []];

                                    foreach($bots as $bot) {
                                        array_push($keyboard["inline_keyboard"], 
                                            [
                                                [
                                                    "text" => $bot->NAMA,
                                                    "url" => $bot->URL
                                                ]
                                            ]
                                        );
                                    }
                                
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ],  "Akun anda berhasil didaftarkan, selanjutnya silahkan pilih bot Scope dibawah ini, jika sudah masuk pada bot pilih Start/Mulai.", $keyboard);
        
                                    sleep(2);
        
                                    $text = "Daftar menu: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                                    $keyboard = [
                                        ['/daftar','/selesai'],
                                    ];
                        
                                    $markupKeyboard = [
                                        "keyboard" => $keyboard,
                                        'resize_keyboard' => true, 
                                        'one_time_keyboard' => true
                                    ];
    
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ],  urlencode($text), $markupKeyboard);
        
                                } else {
    
                                    $keyboard = [
                                        ['/batal'],
                                    ];
                        
                                    $markupKeyboard = [
                                        "keyboard" => $keyboard,
                                        'resize_keyboard' => true, 
                                        'one_time_keyboard' => true
                                    ];
                                    
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ], "PIN yang anda masukkan salah, coba lagi. Masukkan perintah /batal untuk membatalkan perintah", $markupKeyboard);
                                    
        
                                }
    
                            }
    
                        }
    
    
                    } else {
    
                        if(in_array($message, $command)) {
                            
                            if($message == "/daftar") {
    
                                $list_id = DB::table("daftar_id_telegram")->whereRaw("ID_CHAT = ? AND AKTIF = 1", [$chatId])->first();
    
                                if(!$list_id) {
    
                                    DB::table("layanan_detail")->insert([
                                        "ID_LAYANAN" => $layanan->ID_LAYANAN,
                                        "COMMAND" => $message,
                                        "AKTIF" => 1
                                    ]);
    
                                    $keyboard = [
                                        ['/batal'],
                                    ];
                        
                                    $markupKeyboard = [
                                        "keyboard" => $keyboard,
                                        'resize_keyboard' => true, 
                                        'one_time_keyboard' => true
                                    ];
    
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ], "Masukkan PIN anda untuk mengkonfirmasi pendaftaran akun. Pilih /batal untuk membatalkan perintah", $markupKeyboard);
    
                                } else {
    
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ], "Tidak dapat mendaftarkan akun. Saat ini akun telegram anda sudah didaftarkan dengan username: <strong>" . $list_id->USERNAME . "</strong>, jika username anda tidak sesuai, segera hubungi divisi INS");
    
                                    sleep(1);
    
                                    $text = "Daftar menu: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                                    $keyboard = [
                                        ['/daftar','/selesai'],
                                    ];
                        
                                    $markupKeyboard = [
                                        "keyboard" => $keyboard,
                                        'resize_keyboard' => true, 
                                        'one_time_keyboard' => true
                                    ];
    
                                    $this->sendMessage([
                                        "chat_id" => $chatId,
                                        "parse_mode" => "HTML"
                                    ],  urlencode($text), $markupKeyboard);
    
                                }
                                
    
                            } elseif($message == "/batal") {
    
                                DB::table("layanan_detail")->where('ID_LAYANAN', $layanan->ID_LAYANAN)->update([
                                    "AKTIF" => 0,
                                ]);
    
                                $text = "Daftar menu: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                                $keyboard = [
                                    ['/daftar','/selesai'],
                                ];
                    
                                $markupKeyboard = [
                                    "keyboard" => $keyboard,
                                    'resize_keyboard' => true, 
                                    'one_time_keyboard' => true
                                ];
    
                                $this->sendMessage([
                                    "chat_id" => $chatId,
                                    "parse_mode" => "HTML"
                                ], urlencode($text), $markupKeyboard);
    
                            } elseif($message == "/selesai") {
    
                                DB::table("layanan")->where('ID_CHAT', $chatId)->update([
                                    "ID_CHAT" => $chatId,
                                    "AKTIF" => 0,
                                    "UPDATED_AT" => date("Y-m-d H:i:s")
                                ]);
    
                                $this->sendMessage([
                                    "chat_id" => $chatId,
                                    "parse_mode" => "HTML"
                                ], "Terimakasih telah menggunakan layanan ini 😊");
    
                            }
    
                        } else {
    
                            $this->sendMessage([
                                "chat_id" => $chatId,
                                "parse_mode" => "HTML"
                            ], "Maaf, perintah anda tidak dikenali");
                            sleep(1);
                            $text = "Daftar menu: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
    
                            $keyboard = [
                                ['/daftar','/selesai'],
                            ];
                
                            $markupKeyboard = [
                                "keyboard" => $keyboard,
                                'resize_keyboard' => true, 
                                'one_time_keyboard' => true
                            ];
    
                            $this->sendMessage([
                                "chat_id" => $chatId,
                                "parse_mode" => "HTML"
                            ], urlencode($text), $markupKeyboard);
    
                        }
    
    
                    }
                    
    
                }
    
            } else {
    
                $text = "<strong>Selamat datang</strong>, saat ini anda sedang berada di chatbot PAN ERA Group. Untuk melanjutkan, masukkan username dan password anda, <i>cth: mamulyana Loco88-55</i>. Pastikan anda menggunakan perangkat dan akun telegram milik anda sendiri"; 
    
               $this->sendMessage([
                    "chat_id" => $chatId,
                    "parse_mode" => "HTML"
                ], $text);
    
                DB::table("layanan")->insert([
                    "ID_CHAT" => $chatId,
                    "PROFILE_NAME" => $chatName,
                    "CREATED_AT" => date("Y-m-d H:i:s"),
                    "UPDATED_AT" => date("Y-m-d H:i:s")
                ]);
    
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
        $path = "https://api.telegram.org/bot1435233163:AAELGyLnPhH5ktjAP82Wm3MDQs7tAVowZm4";
        if($keyboard) {
            $keyboard = json_encode($keyboard);
            file_get_contents($path.'/sendmessage?text=' . $text . '&reply_markup=' . $keyboard . '&' . http_build_query($content));
        } else {
            file_get_contents($path.'/sendmessage?text=' . $text . '&' . http_build_query($content));
        }
        

    }
    
    public function ppbj()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        
        $chatId = $update["message"]["chat"]["id"];

        try {

            $checkId = DB::table("daftar_id_telegram")->whereRaw("ID_CHAT = ? AND AKTIF = 1", [$chatId])->first();
            
            if($checkId) {

                $this->ppbjSendMessage([
                    "chat_id" => $chatId,
                    "parse_mode" => "HTML"
                ],  "Akun anda sudah terdaftar, anda akan menerima notifikasi approval PPBJ, apabila ada permintaan", );

            } else {

                $bots = DB::table("bot")->whereRaw("KODE = ?", ["general"])->get();

                $keyboard = [ 'inline_keyboard' => []];

                foreach($bots as $bot) {
                    array_push($keyboard["inline_keyboard"], 
                        [
                            [
                                "text" => $bot->NAMA,
                                "url" => $bot->URL
                            ]
                        ]
                    );
                }

                $this->ppbjSendMessage([
                    "chat_id" => $chatId,
                    "parse_mode" => "HTML"
                ],  "Akun anda belum terdaftar, silahkan daftar terlebih dahulu di bot dibawah ini. Anda akan otomatis menerima notifikasi apabila sudah mendaftar.", $keyboard);

            }

        } catch (QueryException $e) {

            $this->ppbjSendMessage([
                "chat_id" => $chatId,
                "parse_mode" => "HTML"
            ],  "Terjadi Kesalahan [" . $e->getCode() . "]");

        }

    }

    public function ppbjSendMessage($content, $text, $keyboard = [])
    {
        $path = "https://api.telegram.org/bot2041978972:AAHUNfNsjaiv1JGHUfqmTfWk0nkXfpnOLJ4";
        if($keyboard) {
            $keyboard = json_encode($keyboard);
            file_get_contents($path.'/sendmessage?text=' . $text . '&reply_markup=' . $keyboard . '&' . http_build_query($content));
        } else {
            file_get_contents($path.'/sendmessage?text=' . $text . '&' . http_build_query($content));
        }
    }

    public function sendPhoto($chatId)
    {
        $path = "https://api.telegram.org/bot1435233163:AAELGyLnPhH5ktjAP82Wm3MDQs7tAVowZm4/";
        $url = $path . "sendPhoto?chat_id=" . $chatId;

        $post_fields = [ 
            'chat_id' => $chatId,
            'photo' => new CURLFile(public_path("uploads/ppbj.jpeg"))
        ];

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:multipart/form-data"
        ));
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
        $output = curl_exec($ch);
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

        $this->ppbjSendMessage([
            "chat_id" => $chatId,
            "parse_mode" => "HTML"
        ], "Hellow", $keyboard);


    }
}
