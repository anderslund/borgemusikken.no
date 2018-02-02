<?php

define('WP_USE_THEMES', false);
require(dirname(__FILE__) . '/wp-blog-header.php');

$epost = $mobil = $adresse = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $funksjon = test_input($_POST["funksjon"]);
    $melding = '';
    switch ($funksjon) {
        case 'passord':
            $melding = doPassord();
            break;

        case 'epost':
            doEpost();
            break;

        case 'mobil':
            doMobil();
            break;

        case 'adresse':
            doAdresse();
            break;
    }

    $url = $_POST["url"];
    if ($melding !== '') {
        $url .= "?melding=" . urlencode($melding);
    }
    header("Location: $url");
}


function doPassord()
{
    $current_pw = test_input($_POST["current_pw"]);
    $new_pw = test_input($_POST["new_pw"]);
    $confirm_pw = test_input($_POST["confirm_pw"]);

    if ($new_pw != $confirm_pw) {
        return "De oppgitte passordene stemmer ikke overens";
    }

    $user = wp_get_current_user();
    if (wp_check_password($current_pw, $user->data->user_pass, $user->ID) == false) {
        return "Feil nåværende passord";
    }

    wp_set_password($new_pw, $user->ID);

    //Relogin
    $creds = array();
    $creds['user_login'] = $user -> user_login;
    $creds['user_password'] = $new_pw;
    wp_signon( $creds, false );
    return "Passord endret";
}

function doEpost()
{
    $epost = test_input($_POST["epost_value"]);
    $user = wp_get_current_user();
    if ($epost == '') {
        $epost = $user->user_login . '@borgemusikken.no';
    }

    wp_update_user(array('ID' => $user->ID, 'user_email' => $epost));
}

function doMobil()
{
    $mobil = test_input($_POST["mobil_value"]);
    if ($mobil == '') {
        $mobil = 'Ikke registrert';
    }
    $user = get_current_user_id();
    update_user_meta($user, 'mobil', $mobil);
}

function doAdresse()
{
    $adresse = test_input($_POST["adresse_value"]);
    if ($adresse == '') {
        $adresse = 'Ikke registrert';
    } else {
        $deler = explode(',', $adresse);
        $poststed = explode(' ', trim($deler[count($deler) - 1]), 2);

        $metas = array(
            'postnr' => $poststed[0],
            'poststed' => $poststed[1]
        );

        for ($i = 0; $i < count($deler) - 1; $i++) {
            $meta_key = 'adresse' . ($i + 1);
            $metas[$meta_key] = trim($deler[$i]);
        }

        //Blanker ut evt elementer som ikke er oppgitt
        for ($i = count($deler) - 1; $i <= 3; $i++) {
            $meta_key = 'adresse' . ($i + 1);
            $metas[$meta_key] = '';
        }

        $user = get_current_user_id();
        foreach ($metas as $key => $value) {
            update_user_meta($user, $key, $value);
        }
    }
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>
