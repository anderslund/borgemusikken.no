<?php

define('WP_USE_THEMES', false);
require(dirname(__FILE__) . '/wp-blog-header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $funksjon = test_input($_POST["funksjon"]);
    $melding = '';
    switch ($funksjon) {
        case 'tirsdager':
            $melding = do_tirsdager();
            break;

        case 'ekstraovelse':
            //    do_ekstraovelser();
            break;

        case 'nytt_medlem':
            $melding = do_nytt_medlem();
            break;

        //Ajax-kall, så returnerer derfor direkte uten å sette melding - de andre skal bli sånn også ;-)
        case 'ny_status':
            header('HTTP/1.1 200 OK');
            echo do_ny_status();
            return;

        case 'ny_rigg':
            header('HTTP/1.1 200 OK');
            echo do_ny_rigg();
            return;
    }

    $url = $_POST["url"];
    if ($melding !== '') {
        $url .= "?melding=" . urlencode($melding);
    }
    header("Location: $url");
}


function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function do_ny_status()
{
    $user_id = test_input($_POST['user_id']);
    $new_status = test_input($_POST['status']);

    update_user_meta($user_id, 'status', $new_status);

    return $new_status;
}

function do_ny_rigg()
{
    $user_id = test_input($_POST['user_id']);
    $new_rigg = test_input($_POST['rigg']);

    update_user_meta($user_id, 'riggegruppe', $new_rigg);

    return $new_rigg;
}


function do_tirsdager()
{

    // Setter opp startdato
    $start = test_input($_POST["startdato"]);
    if ($start == '') {
        return 'Må oppgi startdato';
    }

    $start_dato = strtotime($start);
    if (!$start_dato) {
        return "Ugyldig dato: $start";
    } else if (date("w", $start_dato) != 2) {
        return "Startdato må være på en tirsdag: " . date("w", $start_dato);
    }

    //Setter opp sluttdato. Setter den lik startdato hvis ikke oppgitt.
    $slutt = test_input($_POST["sluttdato"]);
    if ($slutt == '') {
        $slutt = $start;
    }
    $slutt_dato = strtotime($slutt);

    //Prosjektnavn, tittel og slug
    $prosjekt = test_input($_POST["prosjekt"]);
    $tittel = 'Øvelse' . ($prosjekt !== '' ? ", $prosjekt" : '');

    //Setter inn øvelser fra og med startdato til og med tirsdag før eller på sluttdato
    while ($start_dato <= $slutt_dato) {
        $slug = "ovelse-$start_dato";
        $post_id = lag_ovelse($tittel, $slug);
        if ($post_id > 0) {
            lag_ovelse_meta($post_id, $start_dato);

            $kategorier = array(47, 72);
            wp_set_object_terms($post_id, $kategorier, 'tribe_events_cat');
        }

        $start_dato = strtotime("+1 week", $start_dato);
    }
    return 'OK';
}


function do_nytt_medlem()
{
    $fnavn = test_input($_POST["fnavn"]);
    $enavn = test_input($_POST["enavn"]);
    $brukernavn = test_input($_POST["brukernavn"]);
    $passord = test_input($_POST["passord"]);
    $adresse = test_input($_POST["adresse"]);
    $fdato = test_input($_POST["fdato"]);
    $kjonn = test_input($_POST["kjonn"]);
    $mobil = test_input($_POST["mobil"]);
    $epost = test_input($_POST["epost"]);
    $instrument = test_input($_POST["instrument"]);
    $gruppe = test_input($_POST["gruppe"]);
    $riggegruppe = test_input($_POST["riggegruppe"]);
    $medlemsnr = test_input($_POST["medlemsnr_nmf"]);
    $forsikring = 'M'; # Med instrument - standard
    $innmeldt_dato = date('Y-m-d'); # I dag

    $forelopig = '';
    $kommentar_medlemsnr = 'Dette er også ditt medlemsnummer i Norges Musikkorps Forbund, så det kan jo være greit å ta vare på det.';

    # Litt preprosessering av verdier
    # Hvis riggegruppe ikke oppgitt - velg en tilfeldig mellom 1 og 4
    if ($riggegruppe == '') {
        $riggegruppe = rand(1, 4);
    }

    # Hvis epost ikke oppgitt, faker vi en @borgemusikken.no. Vi må ha det for å opprette brukeren i WordPress
    if ($epost == '') {
        $epost = "$brukernavn@borgemusikken.no";
    }

    if ($mobil == '') {
        $mobil = 'Ikke registrert';
    }

    # Forsøker å opprette kontoen
    $user_id = wp_create_user($brukernavn, $passord, $epost);
    if (is_wp_error($user_id)) {
        return $user_id->get_error_message();
    }

    # Hvis vi ikke har fått medlemsnummer i NMF, setter vi brukerID som medlemsnummer
    if ($medlemsnr == '') {
        $medlemsnr = $user_id;
        $forelopig = '<i>foreløpig </i>';
        $kommentar_medlemsnr = 'Vi har ikke fått tak i ditt medlemsnummer i Norges Musikkorps Forbund, '
            . 'men når vi finner det, vil ditt medlemsnummer være det samme som medlemsnummeret hos NMF. ';
    }


    # Setter display name til noe penere enn login_name
    wp_update_user(array('ID' => $user_id, 'display_name' => "$fnavn $enavn"));
    if (is_wp_error($user_id)) {
        return $user_id->get_error_message();
    }

    $message = 'User created. ';


    # Finner navnet på instrumentet
    global $wpdb;
    $instrument = $wpdb->get_var("select name from bmk_instruments where instrument_id=$instrument");

    $metas = array(
        'nickname' => $brukernavn,
        'first_name' => $fnavn,
        'last_name' => $enavn,
        'fodtdato' => $fdato,
        'mobil' => $mobil,
        'instrument' => $instrument,
        'forsikring' => $forsikring,
        'innmeldt' => $innmeldt_dato,
        'kjonn' => $kjonn,
        'gruppeleder' => 'false',
        'styreverv' => '',
        'annet_verv' => '',
        'medlemsnrNmf' => $medlemsnr,
        'riggegruppe' => $riggegruppe,
        'gruppe' => $gruppe,
        'æresmedlem' => 'false',
        'status' => 'A',
        'kontingent' => 'F'
    );

    # Setter adresse (TODO: Dette er duplisert fra bmk-profil.php)
    $deler = explode(',', $adresse);
    $poststed = explode(' ', trim($deler[count($deler) - 1]), 2);

    $metas['postnr'] = $poststed[0];
    $metas['poststed'] = $poststed[1];

    for ($i = 0; $i < count($deler) - 1; $i++) {
        $meta_key = 'adresse' . ($i + 1);
        $metas[$meta_key] = trim($deler[$i]);
    }

    //Blanker ut evt elementer som ikke er oppgitt
    for ($i = count($deler) - 1; $i <= 3; $i++) {
        $meta_key = 'adresse' . ($i + 1);
        $metas[$meta_key] = '';
    }

    foreach ($metas as $key => $value) {
        update_user_meta($user_id, $key, $value);
    }

    $message .= ' Metadata OK. ';

    # Setter rollen
    $user = new WP_User($user_id);
    $user->set_role('subscriber');

    # Leser mal for epost
    $body = file_get_contents(__DIR__ . '/bmk-velkommen.html');
    if (!$body) {
        $message .= 'Klarte ikke å sende epost til brukeren. ';
    } else {

        # Finner navnet på gruppen
        $gruppenavn = $wpdb->get_var("select name from bmk_groups where group_id=$gruppe");

        $grl = $wpdb->get_row('select u.display_name, u.user_email, m.meta_value from wptu_users u, wptu_usermeta m '
            . "where u.ID = (select group_lead_id from bmk_groups where group_id = $gruppe) "
            . "and m.user_id = u.ID and m.meta_key = 'mobil' ");

        # Bytter ut placeholdere
        $body = str_replace('#fornavn#', $fnavn, $body);
        $body = str_replace('#instrument#', $instrument, $body);
        $body = str_replace('#forelopig#', $forelopig, $body);
        $body = str_replace('#medlemsnummer_nmf#', $medlemsnr, $body);
        $body = str_replace('#kommentar_medlemsnr#', $kommentar_medlemsnr, $body);
        $body = str_replace('#brukernavn#', $brukernavn, $body);
        $body = str_replace('#passord#', $passord, $body);
        $body = str_replace('#gruppe#', $gruppenavn, $body);
        $body = str_replace('#riggegruppe#', $riggegruppe, $body);

        $body = str_replace('#grl_navn#', $grl->display_name, $body);
        $body = str_replace('#grl_epost#', $grl->user_email, $body);
        $body = str_replace('#grl_telefon#', $grl->meta_value, $body);

        # Sender mail til brukeren
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($epost, "Velkommen til Borge Musikkorps!", $body, $headers);

        $message .= 'Epost sendt til brukeren. ';
    }
    return $message;
}


function lag_ovelse($tittel, $slug)
{

    // Setup the author, slug, and title for the post
    $author_id = 1;

    // If the page doesn't already exist, then create it
    if (null == get_page_by_slug($slug, OBJECT, 'tribe_events')) {

        // Set the post ID so that we know the post was created successfully
        $post_id = wp_insert_post(
            array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => $author_id,
                'post_name' => $slug,
                'post_title' => $tittel,
                'post_status' => 'private',
                'post_type' => 'tribe_events',
                'post_content' => 'Vanlig øvelse',
            )
        );

        // Otherwise, we'll stop
    } else {

        // Arbitrarily use -2 to indicate that the page with the title already exists
        $post_id = -2;

    } // end if
    return $post_id;
}


function get_page_by_slug($page_slug, $output = OBJECT, $post_type = 'page')
{
    global $wpdb;
    $page = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s", $page_slug, $post_type));
    if ($page) {
        return get_page($page, $output);
    }
    return null;
}


function lag_ovelse_meta($post_id, $start_dato)
{
    $start_tidspunkt = strtotime('19:00:00', $start_dato);
    $start_tidspunkt_utc = strtotime('-1 hour', $start_tidspunkt);
    $slutt_tidspunkt = strtotime('22:00:00', $start_dato);
    $slutt_tidspunkt_utc = strtotime('-1 hour', $slutt_tidspunkt);
    $duration = $slutt_tidspunkt - $start_tidspunkt;

    $metas = array(
        '_EventOrigin' => 'events-calendar',
        '_EventShowMapLink' => '1',
        '_EventShowMap' => '1',
        '_EventStartDate' => strftime('%Y-%m-%d %H:%M:%S', $start_tidspunkt), //'2016-03-01 08:00:00',
        '_EventEndDate' => strftime('%Y-%m-%d %H:%M:%S', $slutt_tidspunkt),
        '_EventStartDateUTC' => strftime('%Y-%m-%d %H:%M:%S', $start_tidspunkt_utc),
        '_EventEndDateUTC' => strftime('%Y-%m-%d %H:%M:%S', $slutt_tidspunkt_utc),
        '_EventDuration' => $duration,
        '_EventVenueID' => '6933',
        '_EventCurrencySymbol' => '',
        '_EventCurrencyPosition' => 'prefix',
        '_EventURL' => '',
        '_EventTimezone' => 'Europe/Oslo',
        '_EventTimezoneAbbr' => 'CET',
        '_EventCost' => '',
        '_EventOrganizerID' => '0',
    );

    foreach ($metas as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
}

?>
