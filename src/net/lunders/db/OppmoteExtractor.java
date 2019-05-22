package net.lunders.db;

import com.mysql.jdbc.Driver;
import sun.reflect.generics.tree.Tree;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.util.Map;
import java.util.TreeMap;

public class OppmoteExtractor {

    private static Map<String, String> users = new TreeMap<>();
    private static Map<String, String> events = new TreeMap<>();
    private static Map<String, Map<String, String>> presences = new TreeMap<>();

    public static void main(String[] args) throws Exception {
        Class.forName(Driver.class.getName());
        Connection conn = DriverManager.getConnection("jdbc:mysql://cpanel44.proisp.no:3306/borgegmr_wp519", "borgegmr", "877870Elfl)7");

        //Setup username -> display name mapping
        ResultSet rsUsers = conn.createStatement().executeQuery("select distinct u.user_login, u.display_name from wptu_users u, bmk_oppmote o where o.user_login = u.user_login and year(o.dato) = 2019");
        while (rsUsers.next()) {
            users.put(rsUsers.getString(1), rsUsers.getString(2));
        }

        //Setup username -> display name mapping
        ResultSet rsEvents = conn.createStatement().executeQuery("select distinct dato, hva,type from bmk_oppmote where year(dato) = 2019 order by dato");
        while (rsEvents.next()) {
            events.put(rsEvents.getString(1) + "_" + rsEvents.getString(3), rsEvents.getString(2));
        }

        for (String user : users.keySet()) {
            ResultSet rsPresences = conn.createStatement().executeQuery("select dato, status from bmk_oppmote where user_login='" + user + "' and year(dato) = 2019");
            Map<String, String> presence = new TreeMap<>();
            presences.put(user, presence);
            while (rsPresences.next()) {
                presence.put(rsPresences.getString(1), rsPresences.getString(2));
            }
        }

        //Skriv ut headerlinje med dato og "hva"
        StringBuilder eventBuilder = new StringBuilder();
        events.forEach((key, value) -> eventBuilder.append(key.substring(0,10)).append("-").append(value).append(";"));
        System.out.println("Navn \\ ;" + eventBuilder);

        users.forEach((userName, displayName) -> {
            StringBuilder userBuilder = new StringBuilder();
            userBuilder.append(displayName).append(";");
            events.forEach((date, eventName) -> {{
                String status = presences.get(userName).get(date.substring(0,10));
                userBuilder.append(status != null ? status : "").append(';');
            }});

            //presences.get(userName).forEach((date, presence) -> userBuilder.append(presence).append(';'));
            System.out.println(userBuilder);
        });
    }
}