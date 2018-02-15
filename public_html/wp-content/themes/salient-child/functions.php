<?php

require_once(get_stylesheet_directory() . '/includes/bmk-functions-oppmote.php');

add_action('wp_enqueue_scripts', 'salient_child_enqueue_styles');
function salient_child_enqueue_styles()
{


    // wp_enqueue_style( 'font-awesome' );
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css', array('font-awesome'));

    if (is_rtl())
        wp_enqueue_style('salient-rtl', get_template_directory_uri() . '/rtl.css', array(), '1', 'screen');
}

add_action('wp_enqueue_scripts', 'ajax_bmk_enqueue_scripts');
function ajax_bmk_enqueue_scripts()
{
    wp_enqueue_script('bmk', '/bmk-ajax.js', array('jquery'), '1.0', true);
}


add_filter('wp_image_editors', 'change_graphic_lib');
function change_graphic_lib($array)
{
    return array('WP_Image_Editor_GD', 'WP_Image_Editor_Imagick');
}


add_filter('ecs_event_bmk_start_list', 'ecs_event_bmk_start_list');
function ecs_event_bmk_start_list()
{
    return '';
}


add_filter('ecs_event_bmk_end_list', 'ecs_event_bmk_end_list');
function ecs_event_bmk_end_list()
{
    return '';
}

/**
 * A filter used in the Tickera plugin to determine whether or not to load certain styles from within that plugin.
 * FontAwesome, in particular, makes trouble for the Salient theme, so this skips loading it from Tickera.
 */
add_filter('tc_use_default_front_css', 'override_tickera_fontawesome');
function override_tickera_fontawesome()
{
    return false;
}


add_filter('ecs_event_start_tag', 'ecs_event_bmk_start_list_item');
function ecs_event_bmk_start_list_item($default_start_tag, $attributes, $event)
{
    $year = tribe_get_start_date($event, false, 'Y');
    $month = tribe_get_start_date($event, false, 'M');
    $day = tribe_get_start_date($event, false, 'j');

    $output = '<article class="regular post type-post-status-publish format-standard hentry">';
    $output .= '<div class="post-content">';
    $output .= '<div class="post-meta no-img">';
    $output .= '<div class="date"><span class="month">' . $month
        . '</span><span class="day">' . $day
        . '</span><span class="year">' . $year
        . '</span></div>';

    $output .= '<div class="nectar-love-wrap" style="visibility: visible;">';
    $output .= '<a href="#" class="nectar-love" title="Love this" style="margin-left: 21px;"> <div class="heart-wrap"><i class="icon-salient-heart-2"></i> <i class="icon-salient-heart loved"></i></div><span class="nectar-love-count">0</span></a></div>';
    $output .= '</div>';


    $output .= '<div class="content-inner article-content-wrap">';
    $output .= $default_start_tag;
    return $output;
}

add_filter('ecs_event_title_tag_start', 'ecs_event_bmk_start_event_header');
function ecs_event_bmk_start_event_header() {
    return '<h2 class="title">';
}


add_filter('ecs_event_title_tag_end', 'ecs_event_bmk_end_event_header');
function ecs_event_bmk_end_event_header() {
    return '</h2>';
}

add_filter('ecs_event_end_tag', 'ecs_event_bmk_end_list_item');
function ecs_event_bmk_end_list_item()
{
    return '</div></div></article>';
}

add_filter('ecs_event_venue_at_text', 'ecs_event_bmk_at_text');
function ecs_event_bmk_at_text() {
    return '';
}

add_filter('ecs_default_contentorder', 'ecs_event_bmk_default_contentorder');
function ecs_event_bmk_default_contentorder() {
    return 'title, venue, thumbnail, excerpt';
}

add_filter( 'ecs_event_excerpt', 'ecs_event_bmk_excerpt');
function ecs_event_bmk_excerpt() {
    return get_the_content();
}

///////////////////////////////////////////////////////////////////////////////////////////
// BMK Customizations
///////////////////////////////////////////////////////////////////////////////////////////

//Shortcode som returnerer årstallet
function year_shortcode()
{
    $year = date('Y');
    return $year;
}

add_shortcode('year', 'year_shortcode');


//Shortcode som returnerer både måned og årstall i tekstformat
function month_year_shortcode()
{
    $month_year = date_i18n('F Y');
    return $month_year;
}

add_shortcode('month_year', 'month_year_shortcode');


//Filter som overstyrer presentasjonen av navnet på private hendelser
function private_event_title()
{
    return '%s';
}

add_filter('private_title_format', 'private_event_title');
$private_title_format = apply_filters('private_title_format', __('Private: %s'), $post);


// Overstyrer default icon types fra menu-icons plugin til ikke å bruke FontAwesome fordi den kludrer det til for Salient
function menu_icons_without_fontawesome()
{
    $menu_icon_types = array(
        'dashicons' => 'Dashicons',
        'elusive' => 'Elusive',
        'foundation-icons' => 'Foundation',
        'genericon' => 'Genericons',
        'image' => 'Image',
        'svg' => 'Svg');
    return $menu_icon_types;
}

add_filter('icon_picker_default_types', 'menu_icons_without_fontawesome');


//Shortcode som returnerer fornavnet til pålogget bruker
add_shortcode('user_firstname', 'bmk_user_firstname');
function bmk_user_firstname()
{
    $user = wp_get_current_user();
    return $user->first_name;
}

//Shortcode som returnerer pathen til upload-katalogen
add_shortcode('uploads', 'bmk_upload_dir');
function bmk_upload_dir()
{
    return get_option('siteurl') . '/wp-content/uploads';
}

//Shortcode som Returnerer URLen til selve siten
function bmk_siteurl()
{
    return get_bloginfo('url');
}

add_shortcode('site_url', 'bmk_siteurl');

##############################################################################
## Shortcode som returnerer personalia for pålogget bruker som HTML
###############################################################################
function bmk_personalia()
{
    $user = wp_get_current_user();
    $user_meta = get_user_meta($user->ID);

    $adresse = format_adresse($user_meta);
    $fodt_dato = format_dato($user_meta['fodtdato'][0]);
    ?>
    <h2>Personalia</h2>
    <ul>
        <li>Navn: <?php echo $user->first_name . ' ' . $user->last_name; ?></li>
        <li>Brukernavn: <?php echo $user->user_login; ?></li>
        <li>Adresse: <?php bmk_create_modifiable('adresse', $adresse) ?> </li>
        <li>Fødselsdato: <?php echo $fodt_dato; ?></li>
        <li>Mobiltelefon: <?php bmk_create_modifiable('mobil', $user_meta['mobil'][0]) ?></li>
        <li>E-post: <?php bmk_create_modifiable('epost', $user->user_email) ?></li>
        <p/>
    </ul>

    <?php
    if ($_REQUEST['melding'] !== '') {
        echo '<div id="melding">';
        echo '<b>' . $_REQUEST['melding'] . '</b>';
        echo '</div>';
    }
    ?>

    <a title="Trykk for å endre passord" onclick="bmk_toggle('passord');">Bytt passord</a>
    <?php bmk_add_form_password(); ?>
    <script>
        function bmk_toggle(objectName) {
            var spanObject = document.getElementById("span_" + objectName);
            var spanStyle = spanObject.style.display;
            spanObject.style.display = spanStyle === "none" ? "block" : "none";
        }
    </script>
    <?php
}

add_shortcode('bmk_personalia', 'bmk_personalia');


/**
 * Formaterer en adresse som HTML. Bruker formatet linje1, linje2, linje3, postnr poststed.
 * @param $user_meta - Metadata om brukeren
 * @return string - Adressen formatert som HTML
 */
function format_adresse($user_meta)
{
    $adresse = trim($user_meta['adresse1'][0]);
    if ($user_meta['adresse2'][0] !== '') {
        $adresse .= ', ' . $user_meta['adresse2'][0];
    }
    if ($user_meta['adresse3'][0] !== '') {
        $adresse .= ', ' . $user_meta['adresse3'][0];
    }
    $adresse .= ', ' . $user_meta['postnr'][0] . ' ' . $user_meta['poststed'][0];
    return $adresse;
}


function format_dato($input_dato)
{
    if ($input_dato == null || $input_dato == '') {
        return 'Ikke registrert';
    }

    $time = strtotime($input_dato); // DateTime::createFromFormat('Y-m-d', $input_dato);
    return date_i18n('j. F Y', $time);

}


function bmk_medlemsinfo()
{
    $user = wp_get_current_user();
    $user_meta = get_user_meta($user->ID);

    global $wpdb;
    $gruppe = $wpdb->get_row($wpdb->prepare("SELECT name, display_name FROM bmk_groups, wptu_users WHERE group_id = '%s' AND ID = group_lead_id", $user_meta['gruppe'][0]))
    ?>
    <h2>Medlemsinformasjon</h2>
    <ul>
        <li>Instrument: <?php echo $user_meta['instrument'][0]; ?></li>
        <li>Innmeldt dato: <?php echo format_dato($user_meta['innmeldt'][0]); ?></li>
        <li>Status: <?php echo bmk_translate_status($user_meta['status'][0]); ?></li>
        <li>Kontingent: <?php echo bmk_translate_kontingent($user_meta['status'][0]); ?></li>
    </ul>
    <h4>Grupper</h4>
    <ul>
        <li>Gruppe: <?php echo $gruppe->name; ?></li>
        <li>Gruppeleder: <?php echo $gruppe->display_name; ?></li>
        <li>Riggegruppe: <?php echo bmk_format_riggegruppe($user_meta['riggegruppe'][0]); ?></li>
    </ul>

    <?php
    $styreverv = $user_meta['styreverv'][0];
    $annet_verv = $user_meta['annet_verv'][0];

    if ($styreverv != '' || $annet_verv != '') {
        echo '<h4>Verv</h4>';
        echo '<ul>';

        if ($styreverv != '') {
            echo "<li>Styreverv: $styreverv</li>";
        }

        if ($annet_verv != '') {
            echo "<li>Verv: $annet_verv</li>";
        }
        echo '</ul>';
    }
}

add_shortcode('bmk_medlemsinfo', 'bmk_medlemsinfo');


function bmk_nmfinfo()
{
    $user = wp_get_current_user();
    $user_meta = get_user_meta($user->ID);
    ?>
    <h2>NMF-informasjon</h2>
    <ul>
        <li>Medlemsnummer: <?php echo $user_meta['medlemsnrNmf'][0]; ?></li>
        <li>Forsikring: <?php echo bmk_translate_forsikring($user_meta['forsikring'][0]) ?></li>
    </ul>

    <?php

}

add_shortcode('bmk_nmfinfo', 'bmk_nmfinfo');

function bmk_create_modifiable($field_name, $current_value)
{
    echo '<a title="Trykk for å endre $field_name" id="a_' . $field_name
        . '" onclick="bmk_toggle(\'' . $field_name . '\');">'
        . $current_value . '</a>';

    bmk_add_form($field_name);
}

function bmk_add_form($id)
{
    echo '<span class="bmk_profil" id="span_' . $id . '" style="display: none">';
    echo '<form id="' . $id . '" method="POST" action="' . site_url() . '/bmk-profil.php">';
    echo '<input type="text" name="' . $id . '_value"/>';
    echo '<input type="hidden" name="url" value="' . $_SERVER['REQUEST_URI'] . '"/>';
    echo '<input type="hidden" name="funksjon" value="' . $id . '"/>';
    echo '</form>';
    echo '</span>';
}


function bmk_add_form_password()
{
    echo '<span class="bmk_profil_passord" id="span_passord" style="display: none">';
    echo '<form id="passord" method="POST" action="' . site_url() . '/bmk-profil.php">';
    echo 'Nåværende passord: <input type="password" name="current_pw"/>';
    echo 'Nytt passord: <input type="password" name="new_pw"/>';
    echo 'Bekreft passord: <input type="password" name="confirm_pw"/>';
    echo '<input type="hidden" name="funksjon" value="passord"/>';
    echo '<input type="hidden" name="url" value="' . get_permalink() . '"/>';
    echo '<input type="submit"/>';
    echo '</form>';
    echo '</span>';
}


function bmk_translate_status($statuskode)
{
    switch (strtolower($statuskode)) {
        case 'a':
            return 'Aktiv';

        case 'p':
            return 'Permittert';

        case 'v':
            return 'Passiv';

        case 's':
            return 'Aspirant';

        case 'u':
            return 'Sluttet';

        case 'e':
            return 'Æresmedlem';

    }
    return 'Aktiv';
}


function bmk_translate_kontingent($statuskode)
{
    switch (strtolower($statuskode)) {
        case 'a':
            return 'Full';

        case 'p':
            return 'Halv';

        case 'v':
            return 'Kvart';

        case 's':
            return 'Kvart';

        case 'e':
            return 'Frivillig';
    }
    return 'Ukjent';
}


function bmk_format_riggegruppe($rg)
{
    return $rg == null || $rg == '' || $rg <= 0 ? 'Ingen' : $rg;
}


function bmk_translate_forsikring($forsikringskode)
{
    switch (strtolower($forsikringskode)) {
        case 'm':
            return 'Forsikret med instrument';

        case 'u':
            return 'Forsikret uten instrument';

        case 'i':
            return 'Ikke forsikret';

        default:
            return "Ukjent ($forsikringskode)";

    }
}


function bmk_last_login($login)
{
    $user = get_userdatabylogin($login);
    update_usermeta($user->ID, 'last_login', current_time('mysql'));
}

add_action('wp_login', 'bmk_last_login');


##########################################################
# Shortcode som lager en liste over gruppemedlemmer.
# Brukes i medlemslisten
##########################################################
function bmk_gruppe($params)
{
    global $wpdb;

    $sql = $wpdb->prepare("
      SELECT temp.user_id, d.name, temp.display_name, temp.user_email, c.meta_value AS mobil, c2.meta_value AS status,
             c3.meta_value AS riggegruppe,
        CASE
          WHEN d.group_lead_id = temp.user_id THEN TRUE ELSE FALSE
        END AS gruppeleder
      FROM (
        SELECT b.user_id, display_name, user_email
        FROM wptu_users a, wptu_usermeta b
        WHERE b.meta_key = 'gruppe'
          AND b.meta_value = (SELECT group_id FROM bmk_groups WHERE name LIKE '%s') AND b.user_id = a.ID
      ) AS temp
      LEFT OUTER JOIN wptu_usermeta c
        ON c.meta_key = 'mobil' AND c.user_id = temp.user_id
      LEFT OUTER JOIN wptu_usermeta c2
        ON c2.meta_key = 'status' AND c2.user_id = temp.user_id
      LEFT OUTER JOIN wptu_usermeta c3
        ON c3.meta_key = 'riggegruppe' AND c3.user_id = temp.user_id
      LEFT OUTER JOIN bmk_groups d
        ON d.group_lead_id = temp.user_id
        and d.name like '%s'
      ORDER BY temp.display_name"
        , $params['navn'] . '%', $params['navn'] . '%');

    $rows = $wpdb->get_results($sql);

    $html = '';
    if ($rows) {
        $html .= '<table>';
        if (wp_is_mobile()) {
            $html .= '<tr><th>Navn</th><th>Mobiltelefon</th></tr>';
            for ($i = 0; $i < $wpdb->num_rows; $i++) {

                //Hopper over de som har sluttet
                if ('U' == $rows[$i]->status) {
                    continue;
                }

                // Gjør om navnet til en epost-link dersom epost er registrert
                $html .= '<tr><td>';
                if ('Ikke registrert' !== $rows[$i]->user_email) {
                    $html .= '<a href="mailto:' . $rows[$i]->user_email . '">' . $rows[$i]->display_name . '</a>';
                }
                else {
                    $html .= $rows[$i]->display_name;
                }

                # Gruppeleder skal ligge både i header og i selve listen. Hvis vi oppdager at medlemmet er gruppeleder
                # her, legger vi inn en liten HTML-blokk først i responsen, slik at det havner øverst.
                if ($rows[$i]->gruppeleder == 1) {
                    $html = '<div>Mobil: ' . format_mobil($rows[$i]->mobil) . ' | Epost: ' . format_epost($rows[$i]->user_email) . '</a></div>' . $html;
                    $html = '<h6>Gruppeleder: ' . $rows[$i]->display_name . '</h6>' . $html;
                }

                if ('A' !== $rows[$i]->status) {
                    $html .= ' (' . bmk_translate_status($rows[$i]->status) . ')';
                }
                $html .= '</td>';


                // Gjør om telefonnr til en telefonnr-link dersom telefonnr er registrert OG vi er på mobil
                $html .= '<td>';
                $html .= format_mobil($rows[$i]->mobil);
                $html .= '</tr>';

            }
        }
        else {
            $html .= '<tr><th>Navn</th><th>Mobiltelefon</th><th>Epost</th></tr>';
            for ($i = 0; $i < $wpdb->num_rows; $i++) {

                //Hopper over de som har sluttet
                if ('U' == $rows[$i]->status) {
                    continue;
                }

                $user_id = $rows[$i]->user_id;
                $html .= '<tr><td><span id="bmk-status-navn-' . $user_id . '">' . $rows[$i]->display_name;

                if ('A' !== $rows[$i]->status) {
                    $html .= ' (' . bmk_translate_status($rows[$i]->status) . ')';
                }

                $html .= '</span>';

                if (current_user_can('editor') || current_user_can('administrator')) {
                    $html .= '<div style="float: right;text-align: right">'
                        . '<a onclick="void(0);" title="" class="bmk-status" data-content="'
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`A`);\'>Aktiv</a> | '
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`V`);\'>Passiv</a> | '
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`P`);\'>Permittert</a> | '
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`S`);\'>Aspirant</a> | '
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`E`);\'>Æresmedlem</a> | '
                        . '<a onclick=\'jQuery.fn.set_status(' . $user_id . ',`U`);\'>Sluttet</a>"> '
                        . 'Status: <span id="bmk-status-' . $user_id . '">' . bmk_translate_status($rows[$i]->status) . '</span></a> '
                        . '| Kont: <span id="bmk-status-kont-' . $user_id . '">' . bmk_translate_kontingent($rows[$i]->status) . '</span>'
                        . '<a onclick="void(0)" title="" class="bmk-rigg" data-content="'
                        . '<a onclick=\'jQuery.fn.set_rigg(' . $user_id . ', `1`);\'>1</a> | '
                        . '<a onclick=\'jQuery.fn.set_rigg(' . $user_id . ', `2`);\'>2</a> | '
                        . '<a onclick=\'jQuery.fn.set_rigg(' . $user_id . ', `3`);\'>3</a> | '
                        . '<a onclick=\'jQuery.fn.set_rigg(' . $user_id . ', `4`);\'>4</a> | '
                        . '<a onclick=\'jQuery.fn.set_rigg(' . $user_id . ', `Fritatt`);\'>Fritatt</a>"> |'
                        . 'Rigg: <span id="bmk-rigg-' . $user_id . '">' . $rows[$i]->riggegruppe . '</a>'
                        . '</div>';
                }


                # Gruppeleder skal ligge både i header og i selve listen. Hvis vi oppdager at medlemmet er gruppeleder
                # her, legger vi inn en liten HTML-blokk først i responsen, slik at det havner øverst.
                if ($rows[$i]->gruppeleder == 1) {
                    $html = '<div>Mobil: ' . format_mobil($rows[$i]->mobil) . ' | Epost: ' . format_epost($rows[$i]->user_email) . '</a></div>' . $html;
                    $html = '<h6>Gruppeleder: ' . $rows[$i]->display_name . '</h6>' . $html;
                }

                $html .= '</td><td>'
                    . format_mobil($rows[$i]->mobil) . '</td><td>'
                    . format_epost($rows[$i]->user_email) . '</td></tr>';
            }
        }
        $html .= '</table>';
    }
    return $html;
}

add_shortcode('bmk_gruppe', 'bmk_gruppe');

function bmk_medlemsfordeling()
{
    global $wpdb;
    $results = $wpdb->get_results(
        "SELECT count(*) FROM wptu_usermeta WHERE meta_key = 'status' AND meta_value = 'A'
          UNION ALL
          SELECT count(*) FROM wptu_usermeta WHERE meta_key = 'status' AND meta_value = 'P'
          UNION ALL
          SELECT count(*) FROM wptu_usermeta WHERE meta_key = 'status' AND meta_value = 'V'", ARRAY_N);

    if ($results) {
        return '<div>Vi er nå ' . $results[0][0] . " aktive, " . $results[1][0] . ' permitterte, og ' . $results[2][0] . ' passive.</div>';
    }
    return '';
}

add_shortcode('bmk_medlemsfordeling', 'bmk_medlemsfordeling');


function bmk_passive()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT display_name FROM wptu_users, wptu_usermeta WHERE meta_key = 'status' AND meta_value = 'V' AND wptu_usermeta.user_id = ID");

    if ($results) {
        $html = '<ul>';
        foreach ($results as $passiv) {
            $html .= '<li>' . $passiv->display_name . '</li>';
        }
        $html .= '</ul>';
    }
    return $html;
}

add_shortcode('bmk_passive', 'bmk_passive');

function bmk_aeresmedlemmer()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT display_name FROM wptu_users, wptu_usermeta WHERE meta_key = 'status' AND meta_value = 'E' AND wptu_usermeta.user_id = ID");

    if ($results) {
        $html = '<ul>';
        foreach ($results as $aeresmedlem) {
            $html .= '<li>' . $aeresmedlem->display_name . '</li>';
        }
        $html .= '</ul>';
    }
    return $html;
}

add_shortcode('bmk_aeresmedlemmer', 'bmk_aeresmedlemmer');


function format_mobil($mobil)
{
    if ('Ikke registrert' !== $mobil) {
        if (wp_is_mobile()) {
            return '<a href="tel:' . $mobil . '">' . $mobil . '</a> ';
        }
        else {
            return $mobil;
        }
    }
    return $mobil;
}


function format_epost($epost)
{
    if ('Ikke registrert' !== $epost) {
        return ' <a href="mailto:' . $epost . '" target="_top" > ' . $epost . '</a> ';
    }
    return $epost;
}

function bmk_neste_ovelse()
{
    global $wpdb;
    $ovelse = $wpdb->get_row("SELECT
  ovelse.post_id,
  ovelse.post_title,
  ovelse.post_content,
  ovelse.start,
  pm2.meta_value AS end,
  p2.post_title  AS sted
FROM (
       SELECT
         post_id,
         post_title,
         post_content,
         meta_value AS start
       FROM wptu_posts p, wptu_postmeta pm
       WHERE ID IN (SELECT object_id
                    FROM wptu_term_relationships
                    WHERE term_taxonomy_id = (SELECT term.term_id
                                              FROM wptu_terms term, wptu_term_taxonomy tax
                                              WHERE term.name = 'Øvelse'
                                                    AND tax.taxonomy = 'tribe_events_cat'
                                                    AND tax.term_id = term.term_id)
       )
             AND pm.meta_key = '_EventStartDate'
             AND current_timestamp < date(pm.meta_value) + INTERVAL 22 HOUR
             AND pm.post_id = p.ID
     ) AS ovelse
  LEFT OUTER JOIN wptu_postmeta pm2
    ON pm2.meta_key = '_EventEndDate'
       AND pm2.post_id = ovelse.post_id
  LEFT OUTER JOIN wptu_posts p2
    ON p2.ID = (SELECT meta_value
                FROM wptu_postmeta pm3
                WHERE pm3.meta_key = '_EventVenueID' AND pm3.post_id = ovelse.post_id)
ORDER BY ovelse.start
LIMIT 1");

    if ($ovelse) {
        $dato = date_i18n('l j. F Y', strtotime($ovelse->start));
        $tid_start = date_i18n('H:i', strtotime($ovelse->start));
        $tid_slutt = date_i18n('H:i', strtotime($ovelse->end));

        $html = '<ul>'
            . '<li>' . ucfirst($dato) . '</li>'
            . '<li>Kl. ' . $tid_start . '-' . $tid_slutt . '</li>'
            . '<li>' . $ovelse->sted . '</li>'
            . '</ul>';

        if ($ovelse->post_content !== '') {
            $html .= $ovelse->post_content;
        }

        return $html;
    }
    else {
        return 'Egenøvelse ;-)';
    }
}

add_shortcode('bmk_neste_ovelse', 'bmk_neste_ovelse');


function bmk_select_grupper()
{
    global $wpdb;
    $results = $wpdb->get_results('SELECT group_id, name FROM bmk_groups');

    if ($results) {
        $html = '<select id="gruppe" name="gruppe">';
        foreach ($results as $gruppe) {
            $html .= "<option value=\"$gruppe->group_id\">$gruppe->name</option>";
        }
        $html .= '</select>';
    }
    return $html;
}

add_shortcode('bmk_select_grupper', 'bmk_select_grupper');


function bmk_select_instrument()
{
    global $wpdb;
    $results = $wpdb->get_results('SELECT instrument_id, name FROM bmk_instruments');

    if ($results) {
        $html = '<select id="instrument" name="instrument">';
        foreach ($results as $instrument) {
            $html .= "<option value=\"$instrument->instrument_id\">$instrument->name</option>";
        }
        $html .= '</select>';
    }
    return $html;
}

add_shortcode('bmk_select_instrument', 'bmk_select_instrument');


function bmk_riggegruppe($params)
{
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT u1.display_name
FROM
  (SELECT ID, display_name
   FROM wptu_users
   WHERE ID IN
         (SELECT user_id
          FROM wptu_usermeta
          WHERE meta_key = 'riggegruppe' AND meta_value = '%s')
  ) AS u1
  JOIN wptu_usermeta u2
    ON u1.ID = u2.user_id
       AND u2.meta_key = 'status'
       AND u2.meta_value IN ('A', 'S', 'E')
ORDER BY u1.display_name", $params['nummer']));

    if ($results) {
        $html = '<ul>';
        foreach ($results as $medlem) {
            $html .= '<li style="text-align: left;">' . $medlem->display_name . '</li>';
        }
        $html .= '</ul>';
    }
    return $html;
}

add_shortcode('bmk_riggegruppe', 'bmk_riggegruppe');


function bmk_terminliste_liste()
{
    global $wpdb;
    $results = $wpdb->get_results("
SELECT
  ovelse.post_id,
  ovelse.post_title,
  ovelse.post_content,
  ovelse.start,
  pm2.meta_value AS slutt,
  p2.post_title  AS sted
FROM (
       SELECT
         post_id,
         post_title,
         post_content,
         meta_value AS start
       FROM wptu_posts p, wptu_postmeta pm
       WHERE ID IN (SELECT object_id
                    FROM wptu_term_relationships
                    WHERE term_taxonomy_id IN (SELECT term.term_id
                                               FROM wptu_terms term, wptu_term_taxonomy tax
                                               WHERE term.name <> 'Øvelse'
                                                     AND tax.taxonomy = 'tribe_events_cat'
                                                     AND tax.term_id = term.term_id)
       )
             AND pm.meta_key = '_EventStartDate'
             AND current_timestamp < date(pm.meta_value)
             AND weekday(date(pm.meta_value)) <> 1
             AND pm.post_id = p.ID
     ) AS ovelse
  LEFT OUTER JOIN wptu_postmeta pm2
    ON pm2.meta_key = '_EventEndDate'
       AND pm2.post_id = ovelse.post_id
  LEFT OUTER JOIN wptu_posts p2
    ON p2.ID = (SELECT meta_value
                FROM wptu_postmeta pm3
                WHERE pm3.meta_key = '_EventVenueID' AND pm3.post_id = ovelse.post_id)
ORDER BY ovelse.start");

    if ($results) {
        $html = '<table><tr><th>Dato</th><th>Hva</th><th>Når</th><th>Hvor</th></tr>';

        setlocale(LC_ALL, 'no_NO.UTF8');
        foreach ($results as $hendelse) {
            $start_date = strtotime($hendelse->start);
            $end_date = strtotime($hendelse->slutt);

            $dato = strftime('%e. %B %Y', $start_date);

            if (date('H:i', $start_date) == '00:00' and date('H:i', $end_date) == '23:59') {
                $klokke = 'Info kommer';
            }
            else {
                $klokke = strftime('%H:%M', $start_date) . ' - ' . strftime('%H:%M', $end_date);
            }

            $html .= '<tr>';
            $html .= "<td>$dato</td>";
            $html .= '<td' . (strlen($hendelse->post_content) > 0 ? " title='$hendelse->post_content'>" : ">") . "<a href=\"https://borgemusikken.no/?p=$hendelse->post_id\">$hendelse->post_title</a></td>";
            $html .= "<td>$klokke</td>";
            $html .= "<td>$hendelse->sted</td>";
            $html .= '</tr>';
        }
        $html .= '</table>';
    }
    else {
        $html = 'Fant ingen hendelser';
    }

    return $html;
}

add_shortcode('bmk_terminliste_liste', 'bmk_terminliste_liste');

?>
