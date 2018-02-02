#!/usr/local/bin/php -q
<?php
require_once(dirname(__FILE__) . '/PlancakeEmailParser.php');

$log_file = "/home/borgegmr/public_html/bmk-innfall.log";

//Read body from the mail sent to innfall@borgemusikken.no
$emailParser = new PlancakeEmailParser(mail_read());
$innfall_tekst = $emailParser->getPlainBody();
error_log("Innfall mottatt: $innfall_tekst\n", 3, $log_file);

//Setup the SQL to insert the infall
$sql = "insert into bmk_innfall (time, innfall) values (current_timestamp(), '"
    . quoted_printable_decode($innfall_tekst)
    . "')";

// Create database connection
$servername = "localhost";
$username = "borgegmr";
$password = "877870Elfl)7";
$database = "borgegmr_wp519";
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    $melding = "Innfallsregistrering feilet. Klarte ikke å koble til database. Prøv igjen senere.\n\n";
    error_log($melding . $conn->error . '\n', 3, $log_file);
    die; // i stillhet, ellers bouncer mailen
}

if ($conn->query($sql) !== TRUE) {
    $melding = "Innfallsregistrering feilet. Klarte ikke å skrive til database. Prøv igjen senere.\n\n";
    error_log($melding . $conn->error . '\n', 3, $log_file);
    die;
}

error_log("Har skrevet innfall til database\n", 3, $log_file);
mail_send($innfall_tekst);

function mail_send($message)
{
    //Den som skal motta svaret er den samme som sendte oppmøterapporten.
    $recipient = 'post@borgemusikken.no';
    $subject = 'Du har mottatt et innfall!';
    $headers = "From: innfall@borgemusikken.no\nContent-Type: text/plain; charset=UTF-8";

    mail($recipient, $subject, quoted_printable_decode($message), $headers);
}


function mail_read($iKlimit = "")
{
    // Purpose:
    //   Reads piped mail from STDIN
    //
    // Arguements:
    //   $iKlimit (integer, optional): specifies after how many kilobytes reading of mail should stop
    //   Defaults to 1024k if no value is specified
    //     A value of -1 will cause reading to continue until the entire message has been read
    //
    // Return value:
    //   A string containing the entire email, headers, body and all.

    // Variable perparation
    // Set default limit of 1024k if no limit has been specified
    if ($iKlimit == "") {
        $iKlimit = 1024;
    }

    // Error strings
    $sErrorSTDINFail = "Error - failed to read mail from STDIN!";

    // Attempt to connect to STDIN
    $fp = fopen("php://stdin", "r");

    // Failed to connect to STDIN? (shouldn't really happen)
    if (!$fp) {
        echo $sErrorSTDINFail;
        exit();
    }

    // Create empty string for storing message
    $sEmail = "";

    // Read message up until limit (if any)
    if ($iKlimit == -1) {
        while (!feof($fp)) {
            $sEmail .= fread($fp, 1024);
        }
    }
    else {
        while (!feof($fp) && $i_limit < $iKlimit) {
            $sEmail .= fread($fp, 1024);
            $i_limit++;
        }
    }

    // Close connection to STDIN
    fclose($fp);

    // Return message
    return $sEmail;
}

?>
