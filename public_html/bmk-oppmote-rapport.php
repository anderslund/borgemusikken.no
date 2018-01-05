<?php

define('WP_USE_THEMES', false);
require(dirname(__FILE__) . '/wp-blog-header.php');

$alt = test_input($_GET["alt"]);
$grupper = test_input($_GET["grupper"]);

$filename = ($alt ? 'alt' : 'tirsdager') . ($grupper ? '_grupper' : '_person') . '.csv';
$sql = $grupper ? sql_grupper($alt) : sql_person($alt);

# Create datbase connection
$servername = "localhost";
$username = "borgegmr";
$password = "877870Elfl)7";
$database = "borgegmr_wp519";
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    //TODO: Return error page
    die;
}

$fh = @fopen('php://output', 'w');

if ($result = $conn->query($sql)) {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment;filename = $filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    $headerDisplayed = false;

    foreach ($result as $row) {
        if (!$headerDisplayed) {
            fputcsv($fh, array_keys($row), ';');
            $headerDisplayed = true;
        }
        fputcsv($fh, $row, ';');
    }
    http_response_code(200);
}
else {
    fputs($fh, "No results");
    http_response_code(500);
}

fclose($fh);
$conn->close();


function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function sql_person($alt)
{
    return "SELECT
  antall_oppmoter.user_login                                                 AS Brukernavn,
  wptu_users.display_name                                                    AS Navn,
  antall_oppmoter.antall                                                     AS Antall_Oppmoter,
  mulige_oppmoter.antall                                                     AS Mulige_Oppmoter,
  round(100 * antall_oppmoter.antall / ifnull(mulige_oppmoter.antall, 1), 1) AS Prosent
FROM
  wptu_users,
  (SELECT
     user_login,
     count(*) AS antall
   FROM bmk_oppmote
   WHERE status = 'M'
         AND year(dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        " GROUP BY user_login) AS antall_oppmoter
  LEFT OUTER JOIN
(SELECT
     user_login,
     count(*) AS antall
   FROM bmk_oppmote
   WHERE status IN('M', 'F')
    AND year(dato) = year(current_date) "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        " GROUP BY user_login) AS mulige_oppmoter
    ON antall_oppmoter . user_login = mulige_oppmoter . user_login
WHERE antall_oppmoter . user_login = wptu_users . user_login
ORDER BY display_name COLLATE utf8_danish_ci;";
}


function sql_grupper($alt)
{
    return "select mott.name, 
        round(100 * ifnull(mott.antall, 0)  / (ifnull(mott.antall, 1)  + ifnull(fravaer.antall, 0)), 1) as prosent
        from
        (SELECT
          g.name,
          o.status,
          count(*) AS antall
        FROM bmk_groups g, wptu_usermeta m, wptu_users u, bmk_oppmote o
        WHERE o.status = 'M' "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        " and year(dato) = year(current_date)
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
           WHERE o.status = 'F' "
        . ($alt ? "" : "AND weekday(dato) = 1 AND (left(type, 1) = 't' OR length(type) < 1) ") .
        " and year(dato) = year(current_date)
        AND m.meta_value = g.group_id
        AND m.meta_key = 'gruppe'
        and m.user_id = u.ID
        and o.user_login = u.user_login group by g.name, o.status) as fravaer
        on mott.name = fravaer.name
        order by prosent desc, mott.name";
}
