<?php

sendVerify();
function sendVerify(){
        // verify phone number then

$url = 'https://api.nexmo.com/verify/json?' . http_build_query([
        'api_key' => 'ecf341e9',
        'api_secret' => 'be5dc0928e1867c1',
        'number' => '923325314863',
        'brand' => 'Elabelz Order verfication'
    ]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
error_log($response);

$data = json_decode($response, true);
        if($data['status']== '0' && $data['request_id']!=''){
print_r($data);
                //send this $data vvariable to scusses page
        }
}

function verifyCheck($data){


// verify the user

        $url = 'https://api.nexmo.com/verify/check/json?' . http_build_query([
                'api_key' => 'API_KEY',
                'api_secret' => $data['request_id'],
                'request_id' => 'ID_RETURNED_IN_THE_VERIFY_RESPONSE',
                'code' => 'APIN'
            ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        error_log($response);
}





//$urls =  "https://api.nexmo.com/verify/json?api_key=ecf341e9&api_secret=be5dc0928e1867c1&number=923006500416&brand=NexmoVerifyTest";



?>
