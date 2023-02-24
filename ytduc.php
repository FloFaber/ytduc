<?php

/*
 * YTDUC - YouTube Direct URL Cache
 * http://github.com/FloFaber/ytduc
 *
 * Copyright (c) 2023 Florian Faber
 * http://www.flofaber.com
 */


// CONF START
if(!defined("YTDLP"))
  define("YTDLP", "/usr/local/bin/yt-dlp");	// path to yt-dlp executable

if(!defined("LOGFILE"))
  define("LOGFILE", "");			// log file location. defaults to error log

if(!defined("CACHEFILE"))
  define("CACHEFILE", "");			// location to sqlite file for caching direct URLs. If empty caching is disabled.

if(!defined("UA"))
  define("UA", "");				// user-agent to be used for yt-dlp
// CONF END


define("L_FATAL", 16);
define("L_ERR", 8);
define("L_WARN", 4);
define("L_INFO", 2);
define("L_DEBUG", 1);


function ytduc_log(string $msg, $level = L_ERR){

  switch($level){
    case L_FATAL:
      $l = "Fatal"; break;
    case L_WARN:
      $l = "Warning"; break;
    case L_INFO:
      $l = "Info"; break;
    case L_DEBUG:
      $l = "Debug"; break;
    default:
      $l = "Error"; break;
  }

  if(LOGFILE){
    error_log($l.": ".$msg, 3, LOGFILE);
  }else{
    error_log($l.": ".$msg);
  }

}


/**
 * Returns the direct youtube-URL to a given video-ID
 * @param string $id The Video-ID
 * @returns string|false The direct URL on success and false on failure
*/
function ytduc(string $id) : string|false
{

  $url = "https://www.youtube.com/watch?v=$id";

  if(!file_exists(YTDLP)){
    ytduc_log(YTDLP." does not exist!", L_FATAL);
    return false;
  }

  if(!is_executable(YTDLP)){
    ytduc_log(YTDLP." is not executable!", L_FATAL);
    return false;
  }



  $count = 0;

  if(CACHEFILE){
    $db = new SQLite3(CACHEFILE);
    $db->exec("CREATE TABLE IF NOT EXISTS cache (video_id varchar(16) not null, url text not null, created bigint)");

    $stmt = $db->prepare("SELECT * FROM cache WHERE video_id = :id AND created >= :created");
    $stmt->bindValue(":id", $id);
    $stmt->bindValue(":created", time() - 6*3600-60); // 5h 59min. Direct yt urls expire after 6h.
    $res = $stmt->execute();
    if(!$res){
      ytduc_log($db->lastErrorMsg(), L_ERR);
      return false;
    }

    while($row = $res->fetchArray(SQLITE3_ASSOC)){
      $count++;
      $direct_url = $row["url"];
      ytduc_log("$id found in cache", L_INFO);
    }
  }


  if(!$count){
    ytduc_log("$id not found in cache. back to crawling again...", L_INFO);

    $cmd = YTDLP." -4 -f 'bestaudio[ext=m4a]/93' --no-playlist -g --user-agent '".UA."' '$url'";
    ytduc_log("CMD: ".$cmd, L_DEBUG);

    $direct_url = trim(shell_exec($cmd));
    if(!$direct_url){ ytduc_log("error getting direct URL", L_ERR); return false; }

    if(CACHEFILE){
      $stmt = $db->prepare("INSERT INTO cache (video_id, url, created) VALUES (:id, :url, :created)");
      $stmt->bindValue(":id", $id);
      $stmt->bindValue(":url", $direct_url);
      $stmt->bindValue(":created", time(), SQLITE3_INTEGER);
      $res = $stmt->execute();

      if(!$res){
        ytduc_log("sql error: ".$db->lastErrorMsg(), L_ERR);
        return false;
      }
    }
  }

  return $direct_url;

}
