<?php

function bmk_oppmote_viser()
{
    $viser_tirsdager = get_vises();

    if (!strcmp('tirsdager', $viser_tirsdager)) {
        $viser = 'tirsdager';
        $vis = 'totalt oppmøte';
    }
    else {
        $viser = 'totalt oppmøte';
        $vis = 'tirsdager';
    }

    $query_param = urlencode($vis);

    return "Viser <span id='viser'>$viser</span>. Trykk "
        . "<a onclick=\"jQuery.fn.set_cookie('tirsdagsovelser', '" . $vis . "');return true;\" href='" . site_url() . "/login/medlemmer?vis=$query_param'>her</a>"
        . " for å vise <span id='vis'>$vis</span>";
}

add_shortcode('bmk_oppmote_viser', 'bmk_oppmote_viser');


function bmk_oppmote_person()
{
    $viser_tirsdag = get_vises();

    global $wpdb;
    $user_login = wp_get_current_user()->user_login;
    $sql = $wpdb->prepare(
        "SELECT
  antall_oppmoter.user_login,
  antall_oppmoter.antall,
  mulige_oppmoter.antall AS mulige,
  round (100 * antall_oppmoter.antall / ifnull(mulige_oppmoter.antall, 1), 1) AS prosent
FROM
  (SELECT
     user_login,
     count(*) AS antall
   FROM bmk_oppmote
   WHERE status = 'M' " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
   AND user_login = %s) AS antall_oppmoter
  LEFT OUTER JOIN
  (SELECT
     user_login,
     count(*) AS antall
   FROM bmk_oppmote
   WHERE status IN ('M', 'F') " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
         AND user_login = %s) AS mulige_oppmoter
    ON antall_oppmoter.user_login = mulige_oppmoter.user_login", $user_login, $user_login);

    $number = '0';
    $forklaring = '';
    $results = $wpdb->get_row($sql);
    if ($results && $results->user_login != null) {
        $number = $results->prosent;
        $forklaring = '(' . $results->antall . ' av ' . $results->mulige . ' mulige)';
    }
    $html = '<div class="nectar-milestone animated-in" data-symbol="%" data-symbol-alignment="default" data-symbol-pos="after" data-symbol-size="62">'
        . '<div style="font-size: 62px; line-height: 62px;" class="number default" data-number-size="62">'
        . "<span>$number</span>"
        . '</div> <div class="subject">Ditt oppmøte ';

    if ($forklaring !== '') {
        $html .= $forklaring;
    }
    $html .= '</div></div>';
    return $html;
}

add_shortcode('bmk_oppmote_person', 'bmk_oppmote_person');


function bmk_oppmote_topp_x_person($parms)
{
    $antall = $parms['antall'];
    $viser_tirsdag = get_vises();

    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT
          antall_oppmoter.user_login,
          wptu_users.display_name,
          round (100 * antall_oppmoter.antall / ifnull(mulige_oppmoter.antall, 1), 1) as prosent
        FROM
          wptu_users ,
          (SELECT
             user_login,
             count(*) AS antall
           FROM bmk_oppmote
           WHERE status = 'M' " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
           GROUP BY user_login) AS antall_oppmoter
          LEFT OUTER JOIN
        (SELECT
             user_login,
             count(*) AS antall
           FROM bmk_oppmote
           WHERE status IN ('M', 'F') " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
           GROUP BY user_login) AS mulige_oppmoter
            ON antall_oppmoter.user_login = mulige_oppmoter.user_login
        where antall_oppmoter.user_login = wptu_users.user_login
        ORDER BY prosent desc, antall_oppmoter.antall desc, display_name collate utf8_danish_ci
        limit $antall", null);

    $result = $wpdb->get_results($sql);
    if ($result) {
        $html = '';
        for ($i = 0; $i < min($antall, $wpdb->num_rows); $i++) {
            $html .= create_progress_bar($result[$i]->display_name, $result[$i]->prosent);
        }
        return $html;
    }
    else {
        return "Ingen data å vise";
    }
}

add_shortcode('bmk_oppmote_topp_x_person', 'bmk_oppmote_topp_x_person');


function bmk_oppmote_din_gruppe()
{
    $viser_tirsdag = get_vises();

    global $wpdb;
    $sql = $wpdb->prepare(
        "
        SELECT status, count(*) AS antall
        FROM bmk_oppmote
        WHERE status IN ('M', 'F') " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
            AND user_login IN (
                SELECT user_login
                FROM wptu_users a, wptu_usermeta b
                WHERE b.meta_key = 'gruppe'
                AND b.meta_value = (SELECT group_id
                                    FROM bmk_groups
                                    WHERE group_id = (SELECT meta_value
                                                      FROM wptu_usermeta
                                                      WHERE meta_key = 'gruppe'
                AND user_id = %s))
            AND b.user_id = a.ID)
        GROUP BY status", get_current_user_id());

    $result = $wpdb->get_results($sql);
    if ($result) {
        $fravaer = $mott = 0;
        foreach ($result as $row) {
            if ($row->status === 'F') {
                $fravaer = $row->antall;
            }
            if ($row->status === 'M') {
                $mott = $row->antall;
            }
        }

        $totalt = $fravaer + $mott;
        $prosent = round(100 * $mott / max($totalt, 1), 1);

        $html = '<div class="nectar-milestone animated-in" data-symbol="%" data-symbol-alignment="default" data-symbol-pos="after" data-symbol-size="62">'
            . '<div style="font-size: 62px; line-height: 62px;" class="number default" data-number-size="62">'
            . "<span>$prosent</span>"
            . '</div> <div class="subject">Din gruppes oppmøte</div></div>';

        return $html;
    }
    else {
        $html = '<div class="nectar-milestone animated-in" data-symbol="" data-symbol-alignment="default" data-symbol-pos="after" data-symbol-size="62">'
            . '<div style="font-size: 62px; line-height: 62px;" class="number default" data-number-size="62">'
            . "<span>:-(</span>"
            . '</div> <div class="subject">Ingen data å vise</div></div>';

        return $html;
    }
}

add_shortcode('bmk_oppmote_din_gruppe', 'bmk_oppmote_din_gruppe');


function bmk_oppmote_topp_x_grupper($parms)
{
    $antall = $parms['antall'];
    $viser_tirsdag = get_vises();

    global $wpdb;
    $sql = $wpdb->prepare(
        "
        select mott.name, round(100 * ifnull(mott.antall, 0)  / (ifnull(mott.antall, 1)  + ifnull(fravaer.antall, 0)), 1) as prosent
        from
        (SELECT
          g.name,
          o.status,
          count(*) AS antall
        FROM bmk_groups g, wptu_usermeta m, wptu_users u, bmk_oppmote o
        WHERE o.status = 'M' " . tirsdag_clause($viser_tirsdag, 'o') . " 
        and year(dato) = year(current_date)
        AND m.meta_value = g.group_id
        AND m.meta_key = 'gruppe'
        and m.user_id = u.ID
        and o.user_login = u.user_login group by g.name, o.status ) as mott
        LEFT outer join
        (SELECT
             g.name,
             o.status,
             count(*) AS antall
           FROM bmk_groups g, wptu_usermeta m, wptu_users u, bmk_oppmote o
           WHERE o.status = 'F' " . tirsdag_clause($viser_tirsdag, 'o') . " 
           and year(dato) = year(current_date)
        AND m.meta_value = g.group_id
        AND m.meta_key = 'gruppe'
        and m.user_id = u.ID
        and o.user_login = u.user_login group by g.name, o.status) as fravaer
        on mott.name = fravaer.name
        order by prosent desc, mott.name
        limit $antall", null
    );

    $result = $wpdb->get_results($sql);
    if ($result) {
        $html = '';
        for ($i = 0; $i < min($antall, $wpdb->num_rows); $i++) {
            $html .= create_progress_bar($result[$i]->name, $result[$i]->prosent);
        }
        return $html;
    }
    else {
        return "Ingen data å vise";
    }
}

add_shortcode('bmk_oppmote_topp_x_grupper', 'bmk_oppmote_topp_x_grupper');


function bmk_oppmote_totalt()
{
    $viser_tirsdag = get_vises();

    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT status, count(*) AS antall
        FROM bmk_oppmote
        WHERE status IN ('M', 'F') " . tirsdag_clause($viser_tirsdag) . " and year(dato) = year(current_date)
        GROUP BY status", null);

    $result = $wpdb->get_results($sql);
    if ($result) {
        $fravaer = $mott = 0;
        foreach ($result as $row) {
            if ($row->status === 'F') {
                $fravaer = $row->antall;
            }
            if ($row->status === 'M') {
                $mott = $row->antall;
            }
        }

        $totalt = $fravaer + $mott;
        $prosent = round(100 * $mott / max($totalt, 1), 1);

        $html = '<div class="nectar-milestone animated-in" data-symbol="%" data-symbol-alignment="default" data-symbol-pos="after" data-symbol-size="62">'
            . '<div style="font-size: 62px; line-height: 62px;" class="number default" data-number-size="62">'
            . "<span>$prosent</span>"
            . '</div> <div class="subject">BMKs oppmøte denne sesongen</div></div>';

        return $html;
    }
    else {
        return 'Ingen data å vise';
    }
}

add_shortcode('bmk_oppmote_totalt', 'bmk_oppmote_totalt');


function bmk_oppmote_historikk($parms)
{

    $antall_aar = $parms['antall_aar'];

    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT aar, prosent
         FROM bmk_oppmote_historikk
         where aar BETWEEN year(current_date) - $antall_aar and year(current_date) -1
         ORDER by aar desc", null);

    $result = $wpdb->get_results($sql);
    if ($result) {
        $html = '';
        foreach ($result as $row) {
            $html .= create_progress_bar($row -> aar, $row -> prosent);
        }

        return $html;
    }
    else {
        return 'Ingen data å vise';
    }
}

add_shortcode('bmk_oppmote_historikk', 'bmk_oppmote_historikk');

function create_progress_bar($name, $prosent)
{
    return '<div class="nectar-progress-bar">'
        . '<p>' . $name . '</p>'
        . '<div class="bar-wrap"><span style="width: ' . $prosent . '%;" class="accent-color" data-width="' . $prosent . '">'
        . '<strong style="opacity: 1;"><i>' . $prosent . '</i>%</strong></span></div>'
        . '</div>';
}

function get_vises()
{
    $vises_cookie = $_COOKIE['tirsdagsovelser'];
    $vises_query_param = $_GET['vis'];

    return $vises_query_param ? $vises_query_param : $vises_cookie;
}


function tirsdag_clause($viser_tirsdag, $tabell_alias = 'bmk_oppmote')
{
    if (strcmp('tirsdager', $viser_tirsdag) == 0) {
        return "and weekday($tabell_alias.dato) = 1 and (left($tabell_alias.type, 1) = 't' or length($tabell_alias.type) < 1)";
    }
    return '';
}


function bmk_oppmote_rapport_individuell($parms)
{
    global $wpdb;
    $alt = $parms['alt'];
    $sql = $wpdb->prepare(
        "SELECT
  antall_oppmoter.user_login                                                 AS Brukernavn,
  wptu_users.display_name                                                    AS Navn,
  antall_oppmoter.antall                                                     AS Antall_Oppmøter,
  mulige_oppmoter.antall                                                     AS Mulige_Oppmøter,
  round(100 * antall_oppmoter.antall / ifnull(mulige_oppmoter.antall, 1), 1) AS Prosent
FROM wptu_users,
  (SELECT user_login, count(*) AS antall FROM bmk_oppmote
    WHERE status = 'M'
    AND year(dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        "GROUP BY user_login) AS antall_oppmoter
LEFT OUTER JOIN
  (SELECT user_login, count(*) AS antall FROM bmk_oppmote
   WHERE status IN ('M', 'F')
         AND year(dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        "GROUP BY user_login) AS mulige_oppmoter
ON antall_oppmoter.user_login = mulige_oppmoter.user_login
WHERE antall_oppmoter.user_login = wptu_users.user_login
ORDER BY display_name COLLATE utf8_danish_ci;", null);

    $result = $wpdb->get_results($sql, null);
    if ($result) {
        $html = '<table><tr><th>Navn</th><th>Antall møtt</th><th>Antall mulige</th><th>Oppmøteprosent</th></tr>';
        foreach ($result as $person) {

            $td = ($person->Prosent == 100) ? "<td><b>" : "<td>";
            $tds = ($person->Prosent == 100) ? "</b></td>" : "</td>";


            $html .= '<tr>';
            $html .= "$td$person->Navn$tds";
            $html .= "$td$person->Antall_Oppmøter$tds";
            $html .= "$td$person->Mulige_Oppmøter$tds";
            $html .= "$td$person->Prosent$tds";
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
    else {
        return "Ingen data å vise";
    }
}

add_shortcode('bmk_oppmote_rapport_individuell', 'bmk_oppmote_rapport_individuell');


function bmk_oppmote_rapport_grupper($parms)
{
    global $wpdb;
    $alt = $parms['alt'];
    $sql = $wpdb->prepare(
        "SELECT
  mott.name,
  round(100 * ifnull(mott.antall, 0) / (ifnull(mott.antall, 1) + ifnull(fravaer.antall, 0)), 1) AS prosent
FROM
  (SELECT
     g.name,
     o1.status,
     count(*) AS antall
   FROM bmk_groups g, wptu_usermeta m, wptu_users u, bmk_oppmote o1
   WHERE o1.status = 'M'
         AND year(o1.dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        "AND m.meta_value = g.group_id
         AND m.meta_key = 'gruppe'
         AND m.user_id = u.ID
         AND o1.user_login = u.user_login
   GROUP BY g.name, o1.status) AS mott
  LEFT OUTER JOIN
  (SELECT
     g.name,
     o2.status,
     count(*) AS antall
   FROM bmk_groups g, wptu_usermeta m, wptu_users u, bmk_oppmote o2
   WHERE o2.status = 'F'
         AND year(o2.dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        "AND m.meta_value = g.group_id
         AND m.meta_key = 'gruppe'
         AND m.user_id = u.ID
         AND o2.user_login = u.user_login
   GROUP BY g.name, o2.status) AS fravaer
    ON mott.name = fravaer.name
ORDER BY prosent DESC, mott.name", null);

    $result = $wpdb->get_results($sql);
    if ($result) {
        $html = '<table><tr><th>Gruppe</th><th>Oppmøteprosent</th></tr>';
        foreach ($result as $gruppe) {

            $td = ($gruppe->prosent == 100) ? "<td><b>" : "<td>";
            $tds = ($gruppe->prosent == 100) ? "</b></td>" : "</td>";


            $html .= '<tr>';
            $html .= "$td$gruppe->name$tds";
            $html .= "$td$gruppe->prosent$tds";
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
    else {
        return "Ingen data å vise";
    }
}

add_shortcode('bmk_oppmote_rapport_grupper', 'bmk_oppmote_rapport_grupper');

