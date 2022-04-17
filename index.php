<?php
//CLIENT

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'helper.php';

global $headers;
$headers = [];

global $headers_as_they_are;
$headers_as_they_are = '';

global $length_written;
$length_written = 0;

try
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "http://stream_server.test");
    curl_setopt($curl, CURLOPT_WRITEFUNCTION, 's_curl_write_flush');
    curl_setopt($curl,  CURLOPT_HEADERFUNCTION, 'curl_head_func');
    curl_setopt($curl,  CURLOPT_PROGRESSFUNCTION, 'progress_func');

    curl_setopt($curl,  CURLOPT_NOPROGRESS, false); //needed for CURLOPT_PROGRESSFUNCTION
    //curl_setopt($curl, CURLOPT_RANGE, '0-1024');
    curl_setopt($curl, CURLOPT_HEADER, false);
    // return the transfer as a string, also with setopt()
    //curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //bad for large files

    $output = curl_exec($curl);


    if ($output === false)
    {
        echo '<h1>' . curl_error($curl) . '</h1>';
        echo '<h1>' . curl_errno($curl) . '</h1>';
    }



    curl_close($curl);
}
catch (Exception $e)
{
    die($e->getMessage());
}

function curl_head_func($ch, $header)
{
    global $headers;
    global $headers_as_they_are;

    $headers_as_they_are .= $header;
    $pieces = explode(":", $header);
    if (count($pieces) >= 2) $headers[trim($pieces[0])] = trim($pieces[1]);
    return strlen($header);
}

function s_curl_write_flush($ch, $chunk)
{
    global $headers;
    global $headers_as_they_are;
    global $length_written;

    if (ob_get_length())
    {
        ob_flush();
        flush();
    }

    if ($chunk && array_key_exists('FileName', $headers))
    {
        $length_written += strlen($chunk);
        if ($length_written <= strlen($headers_as_they_are))
            return strlen($chunk);
        else
        {
            //write single chunk
            $file = fopen($headers['FileName'], 'a');
            fwrite($file, $chunk);
            fclose($file);
        }
    }

    return strlen($chunk); // tell Curl there was output (if any).
}

function progress_func($ch, $dl_bytes_expected, $dl_bytes, $ul_bytes_expected, $ul_bytes)
{
    //track every time s_curl_write_flush has been called
    ob_start();
    echo 'Download Bytes expected: ' . $dl_bytes_expected . PHP_EOL;
    echo 'Downloaded Bytes: ' . $dl_bytes . PHP_EOL;

    file_put_contents('progress.txt', ob_get_clean() . PHP_EOL, FILE_APPEND);

    return 0;
}
