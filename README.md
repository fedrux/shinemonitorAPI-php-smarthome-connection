# shinemonitorAPI-php-smarthome-connection
Connect your Tuya smarthome with ShinemonitorApi/WatchPower of Voltronic Inverter Datalogger

String url in energia.ino deve connettersi esattamente alla pagina energia php messa su un server.
la nodemcu con energia.ino non fa altro che connettersi periodicamente ogni 30 secondi al sito energia.php con intervalli regolari e ricevere in risposta il tipo di segnali led da accendere: blu batteria, bianco modo operativo: eco o sole, e i tre colori Verde,giallo rosso indicano lo stato dell'inverter, se sta caricando o è in modo batteria ad esempio. 
Tutte le richieste IFTTT, telegram, e shinemonitorAPI sono gestite dal file energia.php sul server.
In Smartlife bisogna configurare gli scenari eco e sole, in IFTTT bisogna configurare le 2 richieste webhook che triggerano gli scenari eco e sole.
