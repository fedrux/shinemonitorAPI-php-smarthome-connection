<?php
/*
* 30/10/23
* tutto si basa su https://api.shinemonitor.com/chapter1/demoJava.html
*
*
*/
session_name("energia");
session_start();

$statoUltimo="";
$statoAttuale="";

if(isset($_SESSION["statoUltimo"])){
    //$statoUltimo = $_SESSION["statoUltimo"];
}
if (isset($_GET["statoUltimo"])){
    $statoUltimo = $_GET["statoUltimo"];
}
$temp = 0; $hum = 0;
if (isset($_GET["temp"])){
    $temp = intval($_GET["temp"]);
}
if (isset($_GET["statoUltimo"])){
    $hum = intval($_GET["hum"]);
}
if(isset($_GET["device"])){
    $device = $_GET["device"];
}else{
    $device="Web";
}

function inviaMessaggioTelegram($message){
    global $temp;
    global $hum;
    global $device;

    $message .= "\nTemperatura-> ".$temp."\nUmidita-> ".$hum;
    $message .= "\nDevice: ".$device;

    // Replace 'YOUR_BOT_TOKEN' with your actual bot token
    $botToken = 'YOUR_BOT_TOKEN';
    $chatId = '@XXXXXXXXXXXXXX id chat o group'; // Replace with the chat ID where you want to send the message

// Create a URL for the Telegram Bot API
    $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($message);

// Use cURL to send the message
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        // Handle the cURL error
        echo 'cURL error: ' . curl_error($ch);
    } else {
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['ok']) {
            // Message sent successfully
            echo 'Message sent successfully!';
        } else {
            // Handle the Telegram API error
            echo 'Telegram API error: ' . $responseData['description'];
        }
    }
// Close cURL session
    curl_close($ch);
}


function iftttTrigger($eventName){

    global $device;
    if ($device!== "nodemcu"){
        inviaMessaggioTelegram("Trigger non inviato perchè la richiesta non arriva dal device giusto");
        return;
    }

    //https://ifttt.com/maker_webhooks/my_applets then click on documentation
    $iftttKey = 'XXXXXXXXXXXXXX ifttt key';
    
// Use the event name you created in step 2

    $data = [
        'value1' => 'This is a value you can send to IFTTT',
        'value2' => 'Another value',
        'value3' => 'Yet another value',
    ];

    $iftttUrl = "https://maker.ifttt.com/trigger/$eventName/with/key/$iftttKey";

    $options = [
        'http' => [
            'header' => 'Content-type: application/json',
            'method' => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($iftttUrl, false, $context);
    if ($result === false) {
        echo "\nFailed to trigger IFTTT applet!";
    } else {
        echo "\nIFTTT applet triggered successfully!";
    }
    print_r($result);
    inviaMessaggioTelegram($result);
}

function logToCSV($data, $logFilePath, $maxSize = 10485760 /* 10MB */) {
    // Verifica se il file di log esiste
    if (file_exists($logFilePath) && filesize($logFilePath) >= $maxSize) {
        // Se il file supera la dimensione massima, elimina il file esistente
        unlink($logFilePath);
    }
    // Apri il file in modalità append (aggiunta) o crealo se non esiste
    $file = fopen($logFilePath, 'a');

    if (!$file) {
        die("Impossibile aprire o creare il file di log.");
    }
    // Converti l'array in una stringa CSV
    $csvData = json_encode($data);

    // Scrivi la stringa CSV nel file
    fwrite($file, $csvData . "\n");

    // Chiudi il file
    fclose($file);
    echo "<br><a href='$logFilePath'>File log creato</a><br>";
}




$usrname="XXXXXXXXXXX";
$salt = strval(microtime(true) * 1000); // Salt value
$sha1Pwd = sha1("XXXXXXXXXXX");
$companyKey="XXXXXXXXXXX";
$source="1";
$_app_id_ = "com.eybond.smartclient.ess";
$_app_version_ = "3.26.1.2";
$_app_client_ = "android";
$pn="XXXXXXXXXX";
$sn ="XXXXXXXXXXX";

$passwordPagina = sha1($usrname.$sha1Pwd);
if ($_GET["passwordPagina"] !== $passwordPagina){
    die("Non sei autorizzato ad entrare");
}

#echo $passwordPagina;

function request($url){
    $ch = curl_init("https://api.shinemonitor.com/public/".$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if ($response === false) {
        die('cURL Error: ' . curl_error($ch));
    }
    echo "<br>";
    print_r($response);
    $data = json_decode($response);
    if ($data === null) {
        die('JSON parsing error');
    }
    curl_close($ch);
    return $data;
}

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .div-arduino {
            background-color: #ffcccb;
            padding: 15px;
            border: 2px solid #ff6b6b;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-family: 'Arial', sans-serif;
            color: #333;
        }
        .sistema {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-family: Arial, sans-serif;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="sistema">
        <h1>DIV DEBUG</h1>
        <p>
            <?php
            $action = "&action=auth&usr=" . urlencode($usrname) . "&company-key=" . $companyKey . "&source=" .
                $source . "&_app_id_=" . $_app_id_ . "&_app_version_=" . $_app_version_ . "&_app_client_=" . $_app_client_;
            //echo $action;
            $sign = sha1($salt . $sha1Pwd . $action);
            $request1 = "?sign=" . $sign . "&salt=" . $salt . $action;
            print_r("<br>".$request1);
            $dataUrl1 = request($request1);

            /* Secret and token after authentication. */
            $secret = $dataUrl1->dat->secret;
            $token = $dataUrl1->dat->token;

            function requestParam($actionString){
                global $salt, $source, $_app_id_, $_app_version_, $_app_client_, $pn, $sn, $secret, $token;
                $action = "&action=".$actionString."&devcode=2449&devaddr=1&sn=".$sn."&pn=".$pn . "&source=" . $source . "&_app_id_=" . $_app_id_ . "&_app_version_=" . $_app_version_ . "&_app_client_=" . $_app_client_;
                $sign = sha1($salt . $secret . $token . $action); /* SHA-1(salt + secret + token + action) */
                $request = "?sign=" . $sign . "&salt=" . $salt . "&token=" . $token . $action;
                print_r("<br>".$request);
                return request($request)->dat;
            }


            $reqParam1 = requestParam("querySPDeviceLastData");
            echo "<br><hr><br>";
            $reqParam2 = requestParam("webQueryDeviceEnergyFlowEs");
            echo "<br><hr><br>";

            $arrayParametri = array(
                "timestamp"=> $reqParam1->gts,
                "bt_battery_capacity"=>intval($reqParam2->bt_status[0]->val),
                "bt_battery_charging_current" => intval($reqParam1->pars->bt_[8]->val),
                "bt_battery_discharge_current"=> intval($reqParam1->pars->bt_[9]->val),
                "pv_output_power" => intval(round(floatval($reqParam2->pv_status[0]->val), 3)*1000),
                "grid_active_power" => boolval($reqParam2->gd_status[0]->status),
                "load_active_power" => intval(round(floatval($reqParam2->bc_status[0]->val), 3)*1000),
                "statoAttuale" =>""
            );
            print_r($arrayParametri);
            echo "<br><hr><br>";

            echo "<br><hr><br><h2>INIZIO SERIE DI CONDIZIONI</h2>";

            $messaggio = "";

            $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
            $arrayParametri["led_yellow"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
            $arrayParametri["led_red"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
            $arrayParametri["led_blue"] = [2, $arrayParametri["bt_battery_capacity"]]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio in base allo stato di carica della batteria;


            if($arrayParametri["bt_battery_capacity"] <= 15){
                $statoAttuale = "eco";
                if($arrayParametri["bt_battery_charging_current"] <= 2){
                    $messaggio .=  "\naccendere grid, batteria bassa e non si carica a sufficienza";
                    $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [1, 0];

                    if($arrayParametri["grid_active_power"]){
                        $messaggio .=  "\nGrid già acceso".$arrayParametri["grid_active_power"];
                    }else{
                        $messaggio .=  "\nPERICOLO -> Grid NON acceso".$arrayParametri["grid_active_power"];
                        $arrayParametri["led_green"] = [2, 30]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                        $arrayParametri["led_yellow"] = [2, 60];
                        $arrayParametri["led_red"] = [2, 90];
                    }

                }elseif ($arrayParametri["bt_battery_charging_current"] <= 30){
                    $messaggio .=  "\nbatteria bassa, carica lenta";
                    $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [2, 30];
                }else{
                    $messaggio .=  "\nbatteria bassa, ma carica rapida";
                    $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [2, 60];
                }
            }elseif ($arrayParametri["bt_battery_capacity"] <= 90){
                $statoAttuale = "eco";
                if($arrayParametri["bt_battery_charging_current"] <= 1 and $arrayParametri["bt_battery_discharge_current"]>0){
                    $messaggio .=  "\nModo batteria";
                    $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [2, 50];
                    $arrayParametri["led_red"] = [2, 50];

                }elseif($arrayParametri["bt_battery_charging_current"] <= 50){
                    if($arrayParametri["pv_output_power"] <= 2000){
                        $messaggio .=  "\nbatteria buona, ma carica lenta per bassa produzione";
                        $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                        $arrayParametri["led_yellow"] = [2, 20];
                        $arrayParametri["led_red"] = [1, 0];

                    }else{
                        $messaggio .=  "\nbatteria buona, ma carica lenta per alto carico, ridurre carico dove possibile, 
                spegnere qualche dispositivo, devo recuperare circa ".($arrayParametri["pv_output_power"] - $arrayParametri["load_active_power"])."W";
                        $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                        $arrayParametri["led_yellow"] = [2, 60];
                        $arrayParametri["led_red"] = [0, 0];
                    }
                }else{
                    $messaggio .=  "\nbatteria buona e carica veloce";
                    #$caricoAggiuntivoPossibile = ($arrayParametri["pv_output_power"]-$arrayParametri["load_active_power"]-2500); # 2500W sono i 50A circa di sicurezza
                    # oppure
                    $caricoAggiuntivoPossibile = ($arrayParametri["bt_battery_charging_current"]-50)*50; # 50A di margine, il resto x 50V della batteria, ottengo quanti W posso aggiungere, preferisco questo conto per non variare gli A di carica su cui viene fatto il programma
                    $messaggio .=  "\nSe lascio una carica di sicurezza di 50A posso aggiungere un carico di: ".$caricoAggiuntivoPossibile." W";
                    $arrayParametri["led_green"] = [2, 20]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [1, 0];
                    $arrayParametri["led_red"] = [0, 0];

                    if($caricoAggiuntivoPossibile>=2500 or $arrayParametri["bt_battery_charging_current"] >= 88){ //sopra a 89A rallenta
                        //se posso aggiungere i 2500W di nonna e boiler
                        $statoAttuale = "sole";
                    }else if ($caricoAggiuntivoPossibile>=500){
                        $messaggio .= "Potrei accendere solo nonna isa";
                    }

                }
            }else if ($arrayParametri["bt_battery_capacity"] <= 96){
                if($arrayParametri["bt_battery_charging_current"] <= 25){ //sopra al 90 gli ampere di carica sono massimo 30
                    $messaggio .= "carica lenta o per bassa produzione o per alto carico";
                    if($arrayParametri["pv_output_power"] > $arrayParametri["load_active_power"]){
                        $messaggio .= "\nI pannelli riescono a supportare il carico, accendere qualche dispositivo";
                        $arrayParametri["led_green"] = [1, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                        $arrayParametri["led_yellow"] = [0, 0];
                        $arrayParametri["led_red"] = [0, 0];
                        $statoAttuale = "eco";
                    }else{
                        $messaggio .= "\nI pannelli NON riescono a supportare il carico, spegnere qualche dispositivo, devo recuperare circa ".($arrayParametri["pv_output_power"] - $arrayParametri["load_active_power"])."W";
                        $arrayParametri["led_green"] = [2, 60]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                        $arrayParametri["led_yellow"] = [0, 0];
                        $arrayParametri["led_red"] = [0, 0];
                        $statoAttuale = "eco";
                    }
                }else{
                    $messaggio .= "\nbatteria quasi piena e carica veloce";
                    #$caricoAggiuntivoPossibile = ($arrayParametri["pv_output_power"]-$arrayParametri["load_active_power"]-1500); # 1500W sono i 30A circa di sicurezza
                    # oppure
                    $caricoAggiuntivoPossibile = ($arrayParametri["bt_battery_charging_current"]-25)*50; # 30A di margine, il resto x 50V della batteria, ottengo quanti W posso aggiungere, preferisco questo conto per non variare gli A di carica su cui viene fatto il programma
                    $messaggio .= "\nSe lascio una carica di sicurezza di 25A posso aggiungere un carico di: ".$caricoAggiuntivoPossibile." W";
                    $arrayParametri["led_green"] = [1, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [0, 0];
                    $statoAttuale = "sole";
                }
            }else{
                $statoAttuale = "sole";
                $messaggio .= "\nBatteria strapiena";
                if($arrayParametri["pv_output_power"] > $arrayParametri["load_active_power"]){
                    echo "\nI pannelli riescono a supportare il carico, accendere qualche dispositivo";
                    $arrayParametri["led_green"] = [1, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [0, 0];
                }else{
                    $messaggio .= "\nI pannelli NON riescono a supportare il carico,inutile ridurre carico ora che tanto sono molto alto di carica, però non accendo altro, devo recuperare circa ".($arrayParametri["pv_output_power"] - $arrayParametri["load_active_power"])."W";
                    $arrayParametri["led_green"] = [2, 60]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [2, 80];
                    $arrayParametri["led_red"] = [0, 0];
                }
            }
            $deltaTime = time() - strtotime($arrayParametri["timestamp"]);
            if( $deltaTime > 60*15){ #10 minuti che non aggiorna
                if($deltaTime > 10000 or $deltaTime<1){
                    //non valido
                    inviaMessaggioTelegram("Modalità eco forzata per errore deltaTime");
                    iftttTrigger("eco");
                    $statoAttuale = "eco";
                }else {
                    $messaggio .= "<br>Data vecchia -> deltatime: ";
                    $messaggio .= time() - strtotime($arrayParametri["timestamp"]);
                    $arrayParametri["led_green"] = [0, 0]; # 0 spento, 1 acceso, 2 lampeggio, percentuale lampeggio;
                    $arrayParametri["led_yellow"] = [0, 0];
                    $arrayParametri["led_red"] = [0, 0];
                    $statoAttuale = "eco";
                }

            }else{
                $messaggio .= "<br>Data recente, ok -> deltatime:";
                $messaggio .= time() - strtotime($arrayParametri["timestamp"]);
            }
            $arrayParametri["statoAttuale"]=$statoAttuale;
            echo "<br>";
            echo $messaggio;
            echo $statoAttuale;
            echo "<br>";
            print_r($arrayParametri);
            echo "<br>";

            echo "<br>";
            echo "-".$statoUltimo;
            echo "<br>";
            echo "-".$statoAttuale;
            echo "<br>";



            if($statoAttuale !== $statoUltimo){
                if ($device!== "nodemcu"){
                    print_r("\nTrigger non inviato perchè la richiesta non arriva dal device giusto\n<br>");
                }
                echo "\nqualcosa è cambiato\n";
                inviaMessaggioTelegram($messaggio);
                iftttTrigger($statoAttuale);
                $statoUltimo=$statoAttuale;
                //$_SESSION["statoUltimo"] = $statoAttuale;
            }else{
                echo "<br>Stessa situazione<br>";
            }

            logToCSV($arrayParametri, "log.csv");
            ?>
        </p>
    </div>
    <div class="div-arduino">
        <h1>Div per Arduino</h1>
        <p id="arduino">
            <?php
                //non mettere altre cose in questa sezione altrimenti va tutto a puttane il sistema energia.ino
                $arrayParametri["timestamp"] = strtotime($arrayParametri["timestamp"]);
                echo (json_encode($arrayParametri));
            ?>
        </p>
    </div>

</body>
</html>
