#!/usr/local/bin/php -q
<?php

require(dirname(__FILE__) . '/wp-blog-header.php');
require_once(dirname(__FILE__) . '/PlancakeEmailParser.php');

const OPPMOTE_EGENOVING = 'E';
const OPPMOTE_MOTT = 'M';
const OPPMOTE_SLUTTET = 'S';
const OPPMOTE_PERMISJON = 'P';
const OPPMOTE_FRAVAER = 'F';
const OPPMOTE_PASSIV = 'V';

const STATUS_AERESMEDLEM = 'E';
const STATUS_ASPIRANT = 'S';
const STATUS_SLUTTET = 'U';
const STATUS_PERMITTERT = 'P';
const STATUS_PASSIV = 'V';
const STATUS_AKTIV = 'A';

const LOG_FILE = "/home/borgegmr/public_html/bmk-oppmote.log";

$emailParser = new PlancakeEmailParser(mail_read());
$body = $emailParser->getPlainBody();
$lines = explode("\n", $body);

$sql = 'insert into bmk_oppmote values ';

$i = 1;
$error_lines = array();
$oppmote_dato = null;
$oppmote_type = null;
$oppmote_hva = null;
$medlemsstatus = array();
foreach ($lines as $line) {

    bmk_log($line);
    $line = trim($line);

    # Hvis linja begynner med # eller er blank, hopper vi over
    if ($line == '' || strpos($line, "#") !== false) {
        ++$i;
        continue;
    }

    # Hvis linja begynner med "hva:" , er det en beskrivelse av hva oppmøtet gjelder
    else if (preg_match("/^hva:.*/", strtolower($line)) == 1) {
        $oppmote_hva = trim(substr($line, 4));
    }

    #Linje med dato (yyyy-mm-dd)
    else if (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}/", $line) == 1) {
        $oppmote_dato = substr($line, 0, 10);
        $oppmote_type = get_oppmote_type($line);
    }

    #Linje med oppmøte, f.eks andersl,Anders Lund,M
    else if (strpos($line, ',') > 0) {
        $parts = explode(',', $line);
        $sql .= "('#date#', '" . test_input($parts[0]) . "', '" . test_input($parts[2]) . "', #type#, #hva#),";
        $medlemsstatus[$parts[0]] = $parts[2];
    }

    # Linje med oppdragstype. Hvis linjen er en av de i arrayet, setter vi oppmote_type
    #else if (in_array(strtolower($line), $oppdragstyper)) {
    #    $oppmote_type = strtolower(substr($line, 0, 1));
    #}

    #Linje med feil
    else {
        $error_lines[] = $i;
    }
    ++$i;
}


# Fjerner overflødig siste komma og avslutter SQLen
$sql = rtrim($sql, ",");
$sql .= ' on duplicate key update status = values (status), hva = values (hva)';

if ($oppmote_dato == null) {
    mail_send("Oppmøteregistrering feilet: Vi fant ingen oppmøtedato!\n\n$sql");
    bmk_log("Oppmøteregistrering feilet. Vi fant ingen oppmøtedato!\n\n$sql!");
    die; // i stillhet, ellers bouncer mailen
}


// Erstatter placeholder for dato i SQLen med datoen for oppmøtet
$sql = str_replace("#date#", $oppmote_dato, $sql);

// Erstatter placeholder for type med første to tegn i oppmøtetypen fra mailen, eller null hvis den ikke ble sendt inn.
if ($oppmote_type != null) {
    $sql = str_replace("#type#", "'" . substr($oppmote_type, 0, 2) . "'", $sql);
}
else {
    $sql = str_replace("#type#", 'null', $sql);
}


// Erstatter placeholder for hva med første tegn i oppmøtetypen fra mailen, eller null hvis den ikke ble sendt inn.
if ($oppmote_hva != null) {
    $sql = str_replace("#hva#", "'$oppmote_hva'", $sql);
}
else {
    $sql = str_replace("#hva#", 'null', $sql);
}


// Create connection
$servername = "localhost";
$username = "borgegmr";
$password = "877870Elfl)7";
$database = "borgegmr_wp519";
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    $melding = "Oppmøteregistrering feilet. Klarte ikke å koble til database. Prøv igjen senere.\n\n";
    mail_send($melding);
    bmk_log($melding . $conn->error );
    die; // i stillhet, ellers bouncer mailen
}

//Run the 'query', that is, the uodate
if ($conn->query($sql) !== TRUE) {
    $melding = "Oppmøteregistrering feilet. Klarte ikke å skrive til database. Prøv igjen senere.\n\n";
    mail_send($melding);
    bmk_log($melding . $conn->error );
    die;
}

bmk_log("Har skrevet oppmøte\n");

# Oppdater historikk-tabell
$result = $conn->query("SELECT status, count(*) AS antall FROM bmk_oppmote
        WHERE status IN ('M', 'F') and year(dato) = year(current_date)
        GROUP BY status");
if ($result === FALSE) {
    bmk_log("Fant ikke oppmøte!\n$conn->error");
}
else {
    $fravaer = $mott = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] == 'F') {
            $fravaer = $row['antall'];
        }
        if ($row['status'] == 'M') {
            $mott = $row['antall'];
        }
    }

    $totalt = $fravaer + $mott;
    $prosent = round(100 * $mott / max($totalt, 1), 1);

    bmk_log("Beregnet historikk for dette året - $prosent%");

    if (!$conn->query("insert into bmk_oppmote_historikk
      values(year(CURRENT_DATE), $prosent)
      on duplicate key update prosent=$prosent")) {
        $melding = "Oppmøteregistrering feilet. Klarte ikke å skrive til database. Prøv igjen senere.\n";
        mail_send($melding);
        bmk_log($melding . $conn->error);
        die;
    }
}


# Oppdater medlemsstatuser
bmk_log("Medlemsstatuser\n");
foreach ($medlemsstatus as $brukernavn => $status) {
    $user = get_user_by('login', $brukernavn);
    if (!$user) {
        bmk_log("  *** fant ikke bruker $brukernavn");
        continue;
    }

    $current_status = get_user_meta($user->ID, 'status', true);
    bmk_log("$brukernavn ($user->ID) = Current status: $current_status, new status: $status");

    if ($current_status == STATUS_AERESMEDLEM) {
        bmk_log("  *** Æresmedlem - endrer ikke status");
        continue;
    }
    else if ($status == OPPMOTE_SLUTTET) {
        bmk_log("  *** Setter status til sluttet");
        update_user_meta($user->ID, 'status', STATUS_SLUTTET);
    }
    else if ($status == OPPMOTE_PERMISJON) {
        bmk_log("  *** Setter status til permittert");
        update_user_meta($user->ID, 'status', STATUS_PERMITTERT);
    }
    else if ($status == OPPMOTE_PASSIV) {
        bmk_log("  *** Setter status til passiv");
        update_user_meta($user->ID, 'status', STATUS_PASSIV);
    }
    else if ($status == OPPMOTE_FRAVAER && $current_status != STATUS_ASPIRANT) {
        bmk_log("  *** Fravær - Setter status til aktiv");
        update_user_meta($user->ID, 'status', STATUS_AKTIV);
    }
    else if ($status == OPPMOTE_MOTT && $current_status != STATUS_ASPIRANT) {
        bmk_log("  *** Møtt - Setter status til aktiv");
        update_user_meta($user->ID, 'status', STATUS_AKTIV);
    }
    else if ($status == OPPMOTE_EGENOVING && $current_status != STATUS_ASPIRANT) {
        bmk_log("  *** Egenøving - Setter status til aktiv");
        update_user_meta($user->ID, 'status', STATUS_AKTIV);
    }
}


$conn->close();
bmk_log("Oppmøte for $oppmote_dato er registrert.\n");
mail_send("Oppmøte for $oppmote_dato er registrert. Ha en knællers dag!");


function get_oppmote_type($line)
{
    if (strlen($line) >= 13) {
        $type = strtolower(substr($line, 11, 13));
        return $type !== "t" ? $type : '';
    }
    return null;
}


function mail_send($message)
{
    //Den som skal motta svaret er den samme som sendte oppmøterapporten.
    global $emailParser;
    $recipient = $emailParser->getHeader('from');
    $subject = 'Re: ' . $emailParser->getSubject();
    $headers = 'From: oppmote@borgemusikken.no';

    mail($recipient, $subject, $message, $headers);
}


function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function bmk_log($message)
{
    error_log(date(DATE_ISO8601) . " -- $message\n", 3, LOG_FILE);
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
