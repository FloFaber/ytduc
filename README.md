# YTDUC

YouTube direct URL cache - A small PHP script which retrieves and caches direct YouTube URLs using YT-DLP.

## Usage

```PHP
require_once "ytduc.php";

$id = "OiJKuQtAz3A"; // YouTube Video ID

$direct_url = ytduc($id);

if(!$direct_url){
  echo "Error";
}

echo $direct_url; // https://rr5---sn-h0jelnes.googlevideo.com/videoplayback?expire=1677263827&ei=c6_4Y4eXEt2P6dsPr4y...
```

## Configuration

Configuring this script is done via `define()` before including `ytp.php`.

Those are the available config options:

* `YTDLP` - Path to the yt-dlp executable. Defaults to `/usr/local/bin/yt-dlp`.
* `LOGFILE` - Where to output the log. Defaults to the default error_log.
* `CACHEFILE` - Path to the sqlite3 cache-file. If none is specified caching will be disabled (not recommended).
* `UA` - User agent string used by yt-dlp. Default to empty string.

### Example

```PHP
define("CACHEFILE", "/var/cache/ytp.db");
require_once "ytduc.php";
...
```
