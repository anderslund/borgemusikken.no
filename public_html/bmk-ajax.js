jQuery.fn.set_cookie = function (cookieName, cookieValue) {
    var now = new Date();
    now.setMonth(now.getYear() + 1);
    document.cookie = cookieName + "=" + encodeURIComponent(cookieValue) + ";expires=" + now.toUTCString() + ";secure";
}


jQuery.fn.set_rigg = function (user_id, new_rigg) {
    span_id_rigg = '#bmk-rigg-' + user_id;

    jQuery.ajax({
        url: 'https://borgemusikken.no/bmk-administrasjon.php',
        type: 'post',
        data: {
            'rigg': new_rigg,
            'user_id': user_id,
            'funksjon': 'ny_rigg'
        },
        success: function (data) {
            jQuery(span_id_rigg).html(data);
        }
    });
}


jQuery.fn.set_status = function (user_id, new_status) {
    span_id_status = '#bmk-status-' + user_id;
    span_id_kont = '#bmk-status-kont-' + user_id;
    span_id_navn = '#bmk-status-navn-' + user_id;
    previous_navn = jQuery(span_id_navn).text();

    jQuery.ajax({
        url: 'https://borgemusikken.no/bmk-administrasjon.php',
        type: 'post',
        data: {
            'status': new_status,
            'user_id': user_id,
            'funksjon': 'ny_status'
        },
        success: function (data) {
            jQuery(span_id_status).html(translate_status(data));
            jQuery(span_id_kont).html(translate_kont(data));
            jQuery(span_id_navn).html(translate_navn(previous_navn, data));
        }
    });
};


jQuery.fn.set_gruppeleder = function (group_id, user_id) {

    jQuery.ajax({
        url: 'https://borgemusikken.no/bmk-administrasjon.php',
        type: 'post',
        data: {
            'group_id': group_id,
            'user_id': user_id,
            'funksjon': 'set_gruppeleder'
        },
        success: function () {
            window.location.reload();
        }
    });
};


function translate_navn(previous_navn, status) {
    index = previous_navn.indexOf('(');
    if (index > 0) {
        previous_navn = previous_navn.substring(0, index);
    }

    if ('A' == status) {
        return previous_navn.trim();
    }

    return previous_navn += " (" + translate_status(status) + ")";
}


//TODO: Duplisert
function translate_status(status) {
    switch (status) {
        case "A":
            return "Aktiv";

        case "V":
            return "Passiv";

        case "P":
            return "Permittert";

        case "E":
            return "Æresmedlem";

        case 'S':
            return "Aspirant";

        case 'U':
            return 'Sluttet';

        default:
            return "Aktiv";
    }
}


function translate_kont(status) {
    switch (status) {
        case "A":
            return "Full";

        case "V":
            return "1/4";

        case "P":
            return "Halv";

        case "E":
            return "Frivillig";

        case 'S':
            return "1/4";

        case 'U':
            return '-';

        default:
            return "Full";
    }

}
