<?php

namespace App\Http\Controllers\Tembak;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class XlController extends Controller
{

    function base64URLEncode($buffer)
    {
        $base64 = base64_encode($buffer);
        $base64Url = str_replace(['+', '/', '='], ['-', '_', ''], $base64);
        return $base64Url;
    }

    function sha256($buffer)
    {
        return hash('sha256', $buffer, true);
    }

    function generatePKCE()
    {
        $verifier = $this->base64URLEncode(random_bytes(64));
//        dd($verifier);

//         $verifier = "wcSPiEYtslh7by9_8CRH7p8PW-ocYD0zNOb9Fr7X654";
        $challenge = $this->base64URLEncode($this->sha256($verifier));
        return ['verifier' => $verifier, 'challenge' => $challenge];

    }


    public function coba()
    {

//        $token = $reps7['tokenId'];
//        $pkce = $this->generatePKCE();
//        $codeVerifier = $pkce['verifier'];
//        $codeChallenge = $pkce['challenge'];
        $token = "aabbccdd";
        $codeChallenge = 'wcSPiEYtslh7by9_8CRH7p8PW-ocYD0zNOb9Fr7X654';
        $url = "https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/authorize?iPlanetDirectoryPro=" . $token . "&client_id=a80c1af52aae62d1166b73796ae5f378&scope=openid%20profile&response_type=code&redirect_uri=https%3A%2F%2Fmy.xl.co.id&code_challenge=" . $codeChallenge . "&code_challenge_method=S256";

        // generatePKCE();
        $pkce = $this->generatePKCE();

        $codeVerifier = $pkce['verifier'];
        $codeChallenge = $pkce['challenge'];

        return response()->json([
            'code_verifier' => $codeVerifier,
            'code_challenge' => $codeChallenge
        ]);

//        $data = "belajar saya dulu ";

//        Storage::disk('local')->put('nama_file.txt', $data);
    }


    public function index()
    {
        return view('member.produk.xl');
    }


    public function getOtp(Request $request)
    {

        if ($request->nomor == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'data tidak boleh kosong'
            ], 400);
        }

        $nomor = $request->nomor;
        $date = carbon::now()->format('Y-m-d\TH:i:s.uP');
        $uuid = Str::uuid()->toString();
        $key = "vT8tINqHaOxXbGE7eOWAhA==";

        //cek nomer
        $cekno = Http::withHeaders([
            'Accept-Encoding' => 'gzip',
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
            'Host' => 'api.myxl.xlaxiata.co.id',
            'User-Agent' => 'okhttp/4.3.1',
            'x-api-key' => 'vT8tINqHaOxXbGE7eOWAhA==',
            'x-dynatrace' => 'MT_3_1_2229074692_6-0_24d94a15-af8c-49e7-96a0-1ddb48909564_0_298_64',
            'X-REQUEST-AT' => $date,
            'X-REQUEST-ID' => $uuid,
            'X-VERSION-APP' => '5.8.6',
        ])->post('https://api.myxl.xlaxiata.co.id/infos/api/v1/registration/prepaid', [
            'lang' => 'en',
            'is_enterprise' => false,
            'msisdn' => $nomor,
        ]);

//        return $cekno->getBody()->getContents();

        if ($cekno['status'] === 'FAILED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor XL tidak ditemukan'
            ]);
        }


//       check tipe nomer xl
        $tipexl = Http::withHeaders([
            'Host' => 'api.myxl.xlaxiata.co.id',
            "x-request-id" => $uuid,
            "x-x-request-at" => $date,
            'x-api-key' => $key,
            'x-version-app' => '5.8.6',
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.myxl.xlaxiata.co.id/infos/api/v1/auth/subscriber-info', [
                'lang' => 'en',
                'is_enterprise' => false,
                'msisdn' => $nomor,
            ]);

//      return $tipexl->getBody()->getContents();


        //get auth step 1
        $auth = Http::withHeaders([
            'Host' => 'ciam-rajaampat.xl.co.id',
            'user-agent' => 'okhttp/4.3.1',
        ])
            ->post('https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate?authIndexType=service&authIndexValue=otp');

//       return $auth->getBody()->getContents();

        //ambil data authId
        $authId = $auth['authId'];

        //get auth step 2
        $reps4 = Http::withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'okhttp/4.3.1',
            'Host' => 'ciam-rajaampat.xl.co.id',
            'Content-Type' => 'application/json',
        ])
            ->post('https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate', [
                'authId' => $authId,
                'stage' => 'MSISDN',
                'callbacks' => [
                    [
                        'type' => 'MetadataCallback',
                        'output' => [
                            [
                                'name' => 'data',
                                'value' => [
                                    'stage' => 'MSISDN',
                                ],
                            ],
                        ],
                        '_id' => 0,
                    ],
                    [
                        'type' => 'NameCallback',
                        'output' => [
                            [
                                'name' => 'prompt',
                                'value' => 'MSISDN',
                            ],
                        ],
                        'input' => [
                            [
                                'name' => 'IDToken2',
                                'value' => $nomor
                            ],
                        ],
                        '_id' => 1,
                    ],
                    [
                        'type' => 'HiddenValueCallback',
                        'output' => [
                            [
                                'name' => 'value',
                                'value' => '',
                            ],
                            [
                                'name' => 'id',
                                'value' => 'Language',
                            ],
                        ],
                        'input' => [
                            [
                                'name' => 'IDToken3',
                                'value' => 'MYXLU_AND_LOGIN_EN',
                            ],
                        ],
                        '_id' => 2,
                    ],
                ],
            ]);
//                return $reps4->getBody()->getContents();

        $authId2 = $reps4['authId'];

        //get auth step 3
        //
        $reps5 = Http::withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'okhttp/4.3.1',
            'Host' => 'ciam-rajaampat.xl.co.id',
            'Content-Type' => 'application/json',
        ])
            ->post('https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
                [
                    'authId' => $authId2,
                    'stage' => 'DEVICE',
                    'callbacks' => [
                        [
                            'type' => 'MetadataCallback',
                            'output' => [
                                [
                                    'name' => 'data',
                                    'value' => [
                                        'stage' => 'DEVICE'
                                    ]
                                ]
                            ],
                            '_id' => 4
                        ],
                        [
                            'type' => 'HiddenValueCallback',
                            'output' => [
                                [
                                    'name' => 'value',
                                    'value' => 'Input Device Information'
                                ],
                                [
                                    'name' => 'id',
                                    'value' => 'DeviceInformation'
                                ]
                            ],
                            'input' => [
                                [
                                    'name' => 'IDToken2',
                                    'value' => 'dbac5ca67dd67e1b-bfe1b1262ce9a9462ce79545c1e6656395320898'
                                ]
                            ],
                            '_id' => 5
                        ]
                    ]
                ]);

//                return $reps5->getBody()->getContents();

        $authId3 = $reps5['authId'];
        $subid = $reps5['callbacks'][2]['output'][0]['value'];


        $data = [
            'authId' => $authId3,
            'stage' => 'VALIDATE',
            'callbacks' => [
                [
                    'type' => 'MetadataCallback',
                    'output' => [
                        [
                            'name' => 'data',
                            'value' => [
                                'stage' => 'VALIDATE'
                            ]
                        ]
                    ],
                    '_id' => 7
                ],
                [
                    'type' => 'ConfirmationCallback',
                    'output' => [
                        [
                            'name' => 'prompt',
                            'value' => 'Validate'
                        ],
                        [
                            'name' => 'messageType',
                            'value' => 0
                        ],
                        [
                            'name' => 'options',
                            'value' => [
                                '0 = NO',
                                '1 = YES'
                            ]
                        ],
                        [
                            'name' => 'optionType',
                            'value' => -1
                        ],
                        [
                            'name' => 'defaultOption',
                            'value' => 0
                        ]
                    ],
                    'input' => [
                        [
                            'name' => 'IDToken2',
                            'value' => 1
                        ]
                    ],
                    '_id' => 8
                ],
                [
                    'type' => 'TextOutputCallback',
                    'output' => [
                        [
                            'name' => 'message',
                            'value' => $subid
                        ],
                        [
                            'name' => 'messageType',
                            'value' => '0'
                        ]
                    ],
                    '_id' => 9
                ]
            ]
        ];

        try {
            $reps6 = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'okhttp/4.3.1',
                'Host' => 'ciam-rajaampat.xl.co.id',
                'Content-Type' => 'application/json',
            ])
                ->post('https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
                    $data
                );

//                            return $reps6->getBody()->getContents();
            $authId3 = $reps6['authId'];
            return Response()->json([
                'status' => true,
                'auth_id' => $authId3,
                'message' => 'Berhasil mengirim OTP'
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);

        }

    }

    public function coba1()
    {
        $time = Carbon::now()->addMinutes(5)->timestamp;
        echo $time;
    }

    public function Login(Request $request)
    {

        if ($request->auth_id == null && $request->otp == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'data tidak boleh kosong'
            ], 400);
        }
        $authId = $request->auth_id;

        $otp = $request->otp;
        $time = Carbon::now()->addMinutes(5)->timestamp;

        $data = [
            'authId' => $authId,
            'stage' => 'OTP',
            'callbacks' => [
                [
                    'type' => 'MetadataCallback',
                    'output' => [
                        [
                            'name' => 'data',
                            'value' => [
                                'stage' => 'OTP'
                            ]
                        ]
                    ],
                    '_id' => 0
                ],
                [
                    'type' => 'PasswordCallback',
                    'output' => [
                        [
                            'name' => 'prompt',
                            'value' => 'One Time Password'
                        ]
                    ],
                    'input' => [
                        [
                            'name' => 'IDToken2',
                            'value' => $otp
                        ]
                    ],
                    '_id' => 1
                ],
                [
                    'type' => 'TextOutputCallback',
                    'output' => [
                        [
                            'name' => 'message',
                            'value' => '{"code":"000","data":{"max_validation_attempt_suspend_duration":"900","max_validation_attempt":5,"sent_to":"SMS","next_resend_allowed_at":"0"},"status":"SUCCESS"}'
                        ],
                        [
                            'name' => 'messageType',
                            'value' => '0'
                        ]
                    ],
                    '_id' => 2
                ],
                [
                    'type' => 'ConfirmationCallback',
                    'output' => [
                        [
                            'name' => 'prompt',
                            'value' => ''
                        ],
                        [
                            'name' => 'messageType',
                            'value' => 0
                        ],
                        [
                            'name' => 'options',
                            'value' => ['Submit OTP', 'Request OTP']
                        ],
                        [
                            'name' => 'optionType',
                            'value' => -1
                        ],
                        [
                            'name' => 'defaultOption',
                            'value' => 0
                        ]
                    ],
                    'input' => [
                        [
                            'name' => 'IDToken4',
                            'value' => 0
                        ]
                    ],
                    '_id' => 3
                ]
            ]
        ];

        $reps7 = Http::withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'okhttp/4.3.1',
            'Host' => 'ciam-rajaampat.xl.co.id',
            'Content-Type' => 'application/json',
        ])->post('https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
            $data
        );

//        return $reps7->getBody()->getContents();

//        $code = $reps7['code'];
//
//        if($code == 400){
//            return response()->json([
//                'status' => 'error',
//                'message' => $reps7['message']
//            ]);
//        }else{
//            $token = $reps7['tokenId'];
//        }

        $token = $reps7['tokenId'];
        $pkce = $this->generatePKCE();
        $codeVerifier = $pkce['verifier'];
        $codeChallenge = $pkce['challenge'];
//        $codeChallenge = 'wcSPiEYtslh7by9_8CRH7p8PW-ocYD0zNOb9Fr7X654';
//        $url = 'https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/authorize?iPlanetDirectoryPro=" . $token . "&client_id=a80c1af52aae62d1166b73796ae5f378&scope=openid%20profile&response_type=code&redirect_uri=https%3A%2F%2Fmy.xl.co.id&code_challenge=" . $codeChallenge . "&code_challenge_method=S256';

        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/authorize?iPlanetDirectoryPro=' . $token . '&client_id=a80c1af52aae62d1166b73796ae5f378&scope=openid%20profile&response_type=code&redirect_uri=https%3A%2F%2Fmy.xl.co.id&code_challenge=' . $codeChallenge . '&code_challenge_method=S256',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Host: ciam-rajaampat.xl.co.id',
                    'accept-api-version:  resource=2.1, protocol=1.0',
                    'accept-encoding:  gzip',
                    'user-agent:  okhttp/4.3.1',

                ),
            ));

            $response = curl_exec($curl);
            $redirectUrl = curl_getinfo($curl, CURLINFO_REDIRECT_URL);

            curl_close($curl);
            echo $response;
            // ambil respon $curl header location


            Storage::disk('local')->put('keenam.txt', json_encode($response, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

//        try {
//            $respon10 = Http::withHeaders([
//                'Host' => 'ciam-rajaampat.xl.co.id',
////                'Accept-Api-Version' => 'resource=2.1, protocol=1.0',
//                'User-Agent' => 'okhttp/4.3.1',
////                'Accept-Encoding' => 'gzip, deflate, br',
//            ])->get('https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/authorize', [
//                'iPlanetDirectoryPro' => $token,
//                'client_id' => 'a80c1af52aae62d1166b73796ae5f378',
//                'scope' => 'openid profile',
//                'response_type' => 'code',
//                'redirect_uri' => 'https://my.xl.co.id',
//                'code_challenge' => $codeChallenge,
//                'code_challenge_method' => 'S256',
//            ]);
//
//            Storage::disk('local')->put('keenam.txt', json_encode($respon10, JSON_PRETTY_PRINT));
//
//        } catch (\Exception $e) {
//            return response()->json([
//                'status' => 'error',
//                'message' => $e->getMessage()
//            ]);
//        }


//        $responseDetails = [
//            'status' => $respon10->status(),
//            'headers' => $respon10->header('Location'),
//            'body' => $respon10->body(),
//            'tokenid' => $token,
//            'codeChallenge' => $codeChallenge,
//            'codeVerifier' => $codeVerifier,
//
//        ];
//
//        Storage::disk('local')->put('tujuh.txt', json_encode($responseDetails, JSON_PRETTY_PRINT));

//        $fileContent = "Hello, ini adalah isi file yang akan ditulis.";
//        $filePath = storage_path('app/myfile.txt'); // Contoh path file
//
//        file_put_contents($filePath, $responseDetails);
//
//        return response()->json([
//            'status' => 'success',
//            'message' => 'Berhasil Login',
//            'data' => $responseDetails
//        ]);

    }


    // sukses get prroofile
    public function GetProfile(Request $request)
    {

        $url = "https://api.myxl.xlaxiata.co.id/api/v1/profile";
        $uuid = Str::uuid()->toString();
        $date = carbon::now()->format('Y-m-d\TH:i:s.uP');
        try {
            $resp = Http::withHeaders([
                "Host" => "api.myxl.xlaxiata.co.id",
                "x-dynatrace" => "MT_3_2_3726932852_75-0_24d94a15-af8c-49e7-96a0-1ddb48909564_0_147_95",

                "x-api-key" => "vT8tINqHaOxXbGE7eOWAhA==",
                "x-request-id" => $uuid,
                "x-x-request-at" => $date,
                "x-version-app" => "5.8.6",
                "user-agent" => "okhttp/4.3.1",
                "Content-Type" => "application/json",
                "content-length" => "62",
                "accept-encoding" => "gzip",
            ])->post($url, [
                "lang" => "en",
                "is_enterprise" => false,
                "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJjN2U0YTBlZi1iNWRlLTRhYzMtYTMyZS1iYTM4NmY4ZmI3NDMiLCJjdHMiOiJPQVVUSDJfR1JBTlRfU0VUIiwiYXV0aF9sZXZlbCI6MCwiYXVkaXRUcmFja2luZ0lkIjoiN2YxMWM2ODktZTAyNy00NmUyLWIxMWUtN2M1NmZiYzU5ODhkLTI3MTA3NjgxIiwiaXNzIjoiaHR0cDovL2NpYW0tcmFqYWFtcGF0LnhsLmNvLmlkOjgwL2FtL29hdXRoMi94bCIsInRva2VuTmFtZSI6ImFjY2Vzc190b2tlbiIsInRva2VuX3R5cGUiOiJCZWFyZXIiLCJhdXRoR3JhbnRJZCI6IlRJR3lCTGN1YXJhQ2hwVWh3NnhVNk12MlQxVS4xRVhuQU9RaGFiS2lOcnVZMm4wVUJEVDZQd2MiLCJhdWQiOiJhODBjMWFmNTJhYWU2MmQxMTY2YjczNzk2YWU1ZjM3OCIsIm5iZiI6MTcwMTUzNDI4NywiZ3JhbnRfdHlwZSI6InJlZnJlc2hfdG9rZW4iLCJzY29wZSI6WyJvcGVuaWQiLCJwcm9maWxlIl0sImF1dGhfdGltZSI6MTcwMTUzMDY4NSwicmVhbG0iOiIveGwiLCJleHAiOjE3MDE1Mzc4ODcsImlhdCI6MTcwMTUzNDI4NywiZXhwaXJlc19pbiI6MzYwMCwianRpIjoiVElHeUJMY3VhcmFDaHBVaHc2eFU2TXYyVDFVLmZ2c1FWZW5IQXc4UUUtUV9zdEsyeFU1b3VkbyJ9.0m4OI5n0RtKNmIWsPFwKjjwV92bSUtFnat0T3Bk24nA",
                "app_version" => "5.8.6",
            ]);
            return $resp->getBody()->getContents();


        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
