<?php

/*
 *
 * Author: Plamen Todorov (me-at-ptodorov(.)com)
 * Github: https://github.com/ptodorov0
 *
 * Thanks to Peter Clarke (https://github.com/theapi) for the LCDProc client library.
 */

use Theapi\Lcdproc\Client;

require 'client.php';

// Prevent script from running multiple times simultaneously
$running = exec("ps aux | grep ". basename(__FILE__) ." | grep -v grep | wc -l");
if($running > 2) { // >2 beacause when started by cron there are two processes for each instance of PHP script running
    echo "[WARN] This script is already running in another process. Quitting!\n";
    exit;
}

$playing = getPlaying();
$read = time();

$client = new Client();
$fp = $client->start('127.0.0.1', 13666);

$client->write('client_set name "song"');
$client->write('screen_add song_screen'); // Add screen
$client->write('screen_set song_screen 1 -heartbeat off'); // set current screen and remove heartbeat icon
$client->write('widget_add song_screen 1 scroller'); // create first scroller widget, will be used for row 1
$client->write('widget_add song_screen 2 scroller'); // create second scroller widget, will be used for row 2

// 1 Scroller parameters: left(1) top(1) right(16) bottom(1) direction(m) speed(3) {text}
$client->write('widget_set song_screen 1 1 1 16 1 m 3 {'.$playing[0].'}'); // Add currently playing info line 1 to scroller 1 at row 1

// 2 Scroller parameters: left(1) top(2) right(16) bottom(2) direction(m) speed(3) {text}
$client->write('widget_set song_screen 2 1 2 16 2 m 3 {'.$playing[1].'}'); // Add currently playing info line 2 to scroller 2 at row 2

$client->read();

$ignored = true;

while(!feof($fp)) {

    $line = $client->read();

    if ($line === "") {

       continue;
    } else {

        if (trim($line) == 'listen song_screen') {

            $ignored = false;
        } else if (trim($line) == 'ignore song_screen') {

            $ignored = true;
        }
    }

    if (!$ignored) {

      // Update reading every 5 seconds
      if (time() - $read > 5) {

        // Reset last reload timer
        $read = time();

        echo "[".date("H:i:s d.m.Y", time())."] Updating LCD now playing...\n";

        $playing = getPlaying();

        // Update text in scroller widgets
        $client->write('widget_set song_screen 1 1 1 16 1 m 3 {'.$playing[0].'}');
        $client->write('widget_set song_screen 2 1 2 16 2 m 3 {'.$playing[1].'}');
      }
    }

    sleep(1); // 1 sec
}

function getPlaying() {

  $handle = fopen("/var/local/www/currentsong.txt", "r");

  if ($handle) {

    $current_song = array();
    $result = array();

    // Convert currentsong.txt file format to array
    while (($line = fgets($handle)) !== false) {

      $param_k_v = explode("=", $line);

      $current_song[trim($param_k_v[0])] = trim($param_k_v[1]);
    }

    // Close file
    fclose($handle);

    // IF: the playback is currently stopped (not the same as paused)
    if(isset($current_song["state"]) && $current_song["state"] == "stop") { // If current state = stop

      $result[0] = "STOP";
      $result[1] = "";
    } 

    // ELSEIF: Artist and title are present
    elseif(isset($current_song["artist"]) && isset($current_song["title"]) && strlen($current_song["title"]) > 1) { 
 
      // Outrate is present and contains more than 1 component
      if(isset($current_song["outrate"]) && strpos($current_song["outrate"], ",") !== false) {

        // Extract outrate to second comma, which includes kbps and khz
        $bitrate = substr($current_song["outrate"], 0, strpos($current_song["outrate"], ',', strpos($current_song["outrate"], ',') + 1));
      } 

      $result[0] = $current_song["artist"] . ((isset($bitrate)) ? " (".$bitrate.")" : "");
      $result[1] = $current_song["title"] . ((isset($current_song["state"]) && $current_song["state"] == "pause") ? " (Paused)" : "");

    } 

    // ELSE: Playback is not stopped and artist or title are not present
    else { 

      if(substr_count($current_song["file"], "/") > 0) {

        $result[0] = "";
        $result[1] = end(explode("/", $current_song["file"]));
      } else {

        $result[0] = "";
        $result[1] = $current_song["file"];
      }
    }

    return $result;
  }

} // EO getPlaying()
