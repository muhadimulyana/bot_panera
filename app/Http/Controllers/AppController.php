<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    public function index()
    {
        session_start();
        $path = "https://api.telegram.org/bot1435233163:AAELGyLnPhH5ktjAP82Wm3MDQs7tAVowZm4";
        $update = json_decode(file_get_contents("php://input"), TRUE);
        
        $chatId = $update["message"]["chat"]["id"];
        $chatName = $update["message"]["chat"]["first_name"];
        $message = $update["message"]["text"];

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

                        $text = "Halo <strong>$user->Nama</strong> 😃 Berikut ini list perintah yang dapat digunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";

                        $text = urlencode($text);

                        file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');

                    } else {

                        file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=Username atau password salah, coba lagi&parse_mode=HTML');

                    }

                } else {

                    file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=Format masukkan salah, pastikan format yang anda masukkan sudah benar&parse_mode=HTML');
                    sleep(1);
                    file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=<i>cth: mamulyana a1</i>&parse_mode=HTML');

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

                            file_get_contents($path."/sendmessage?chat_id=".$chatId."&text=Terimakasih telah menggunakan layanan ini 😊");
    
                        } elseif($message == "/batal") {
                            
                            DB::table("layanan_detail")->where('ID_LAYANAN', $layanan->ID_LAYANAN)->update([
                                "AKTIF" => 0,
                            ]);
    
                            $text = "List perintah yang dapat anda gunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
                            $text = urlencode($text);
                            file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');
                            
                        } else {

                            if($message == $layanan->PIN) {
                                
                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=Akun anda berhasil didaftarkan untuk menerima notifikasi&parse_mode=HTML');
    
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
    
                                sleep(1);
    
                                $text = "List perintah yang dapat anda gunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
                                $text = urlencode($text);
                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');
    
                            } else {
    
                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=PIN yang anda masukkan salah, coba lagi&parse_mode=HTML');
    
                            }

                        }


                    }


                } else {

                    if(in_array($message, $command)) {
                        
                        if($message == "/daftar") {

                            $list_id = DB::table("daftar_id_telegram")->whereRaw("ID_CHAT = ?", [$chatId])->first();

                            if(!$list_id) {

                                DB::table("layanan_detail")->insert([
                                    "ID_LAYANAN" => $layanan->ID_LAYANAN,
                                    "COMMAND" => $message,
                                    "AKTIF" => 1
                                ]);
    
                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=Masukkan PIN anda, masukkan perintah /batal untuk membatalkan perintah&parse_mode=HTML');

                            } else {

                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text=Tidak dapat mendaftarkan akun. Saat ini akun telegram anda sudah didaftarkan dengan username: ' . $list_id->USERNAME . ', jika username anda tidak sesuai, segera hubungi divisi INS&parse_mode=HTML');

                                sleep(1);

                                $text = "List perintah yang dapat anda gunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
                                $text = urlencode($text);
                                file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');

                            }
                            

                        } elseif($message == "/batal") {

                            DB::table("layanan_detail")->where('ID_LAYANAN', $layanan->ID_LAYANAN)->update([
                                "AKTIF" => 0,
                            ]);

                            $text = "List perintah yang dapat anda gunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
                            $text = urlencode($text);
                            file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');

                        } elseif($message == "/selesai") {

                            DB::table("layanan")->where('ID_CHAT', $chatId)->update([
                                "ID_CHAT" => $chatId,
                                "AKTIF" => 0,
                                "UPDATED_AT" => date("Y-m-d H:i:s")
                            ]);

                            file_get_contents($path."/sendmessage?chat_id=".$chatId."&text=Terimakasih telah menggunakan layanan ini 😊");

                        }

                    } else {

                        file_get_contents($path."/sendmessage?chat_id=".$chatId."&text=Maaf, perintah anda tidak dikenali");
                        sleep(1);
                        $text = "List perintah yang dapat anda gunakan: \n\n /daftar - Perintah ini digunakan untuk mendaftarkan username anda, sehingga ID telegram anda memungkinkan untuk menerima notifikasi dari program Scope PAN ERA GROUP \n\n /selesai - Perintah ini digunakan untuk mengakhiri sesi layanan";
                        $text = urlencode($text);
                        file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');

                    }


                }
                

            }

        } else {

            $text = "<strong>Selamat datang</strong>, saat ini anda sedang berada di chatbot PAN ERA Group. Untuk melanjutkan, masukkan username dan password anda, <i>cth: mamulyana Loco88-55</i>. Pastikan anda menggunakan perangkat dan akun telegram milik anda sendiri"; 

            file_get_contents($path.'/sendmessage?chat_id='.$chatId.'&text='.$text.'&parse_mode=HTML');

            DB::table("layanan")->insert([
                "ID_CHAT" => $chatId,
                "PROFILE_NAME" => $chatName,
                "CREATED_AT" => date("Y-m-d H:i:s"),
                "UPDATED_AT" => date("Y-m-d H:i:s")
            ]);

        }

    }

    public function test()
    {
        $layanan = DB::table("layanan")->whereRaw("AKTIF = 1")->first();
    }
}
