<?php

require_once "FreeRoomFinder.php";
const BASEURL = "http://localhost/BounCoursePlanner/";

if (!empty($_POST) && array_key_exists("room", $_POST)) {
    $room = trim($_POST["room"]);
    $day = array_key_exists("day", $_POST) ? $_POST["day"] : "";
    $hour = array_key_exists("hour", $_POST) ? $_POST["hour"] : "";
    $rfinder = new FreeRoomFinder();
    if (!empty($day)) $rfinder->filterByDay($day);
    if (!empty($hour)) $rfinder->filterByHour($hour);
    $rooms = $rfinder->searchRoom($room, false);
    die(json_encode($rooms));
}

if (php_sapi_name() == "cli") {

    if (!array_key_exists(1, $argv)) {
        echo "Usage: php " . pathinfo(__FILE__, PATHINFO_BASENAME) . " [Room] [Day] [Hour]" . PHP_EOL;
        echo " [Room] \tFull room code or part of it. (NH,NH1,NH101 etc.)" . PHP_EOL;
        echo " [Day]\t\tOptional. Filters free rooms by day. Abbreviations are ok. (T,Tue,W,Wednesday etc.) " . PHP_EOL;
        echo " [Hour]\t\tOptional. Filters free rooms by hour. UTC+2 (Turkey timezone) hour, not lecture hours! (9,10,15 etc.) " . PHP_EOL;
        die();
    }
    array_walk($argv, function (&$v) {
        return $v = trim($v, "\xC2\xA0\n");
    });
    $rfinder = new FreeRoomFinder();
    if (array_key_exists(2, $argv) && $rfinder->filterByDay($argv[2]) === false) {
        echo "- Day is not valid, continuing without using it." . PHP_EOL;
    }
    if (array_key_exists(3, $argv) && $rfinder->filterByHour($argv[3]) === false) {
        echo "- Hour is not valid, continuing without using it." . PHP_EOL;
    }
    if ($rfinder->searchRoom($argv[1]) === false) {
        echo "- Given room couldn't be found, check if it's correct." . PHP_EOL;
    }

} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Science Machine</title>
        <link rel="stylesheet" href="<?php echo BASEURL; ?>assets/css/bootstrap.min.css">
        <style type="text/css">
            .list-group-item {
                display: inline-flex !important;
                width: 100%;
                font-size: 1.4rem;
            }

            input[type="text"] {
                margin: 5px 0;
            }

            .vcenter {
                display: flex;
                align-items: center;
                vertical-align: middle;
                float: none;
                justify-content: center;
                padding-right: 100px;
            }

            @media(max-width: 420px){
                .list-group-item {
                    display: inline-table !important;
                }
            }

        </style>
    </head>
    <body>
    <h1 class="text-center" style="padding-bottom: 20px">Science Machine</h1>
    <div class="container text-center">
        <div class="well well-lg">
            <div class="form-group form-inline">
                <input type="text" class="form-control" id="room" placeholder="Room">
                <input type="text" class="form-control" id="day" placeholder="Day (Optional)">
                <input type="text" class="form-control" id="hour" placeholder="Hour (Optional)">
            </div>
            <button type="submit" class="btn btn-primary" id="send">Do the Magic</button>
        </div>
    </div>
    <div class="container">
        <ul class="list-group" id="items">
        </ul>
    </div>
    <script src="<?php echo BASEURL; ?>assets/js/jquery.min.js"></script>
    <script src="<?php echo BASEURL; ?>assets/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#send").on('click', function () {
                var room = $("#room").val();
                var day = $("#day").val();
                var hour = $("#hour").val();
                var itemsField = $("#items");
                var returnData;
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    dataType: 'json',
                    async: false,
                    data: {
                        room: room,
                        day: day,
                        hour: hour
                    },
                    success: function (ret) {
                        returnData = ret;
                        itemsField.html("");
                        if (ret.length == 0) {
                            itemsField.html("<h4 class=\"text-center\">No free rooms.</h4>");
                        } else {
                            for (var i in ret) {
                                if (ret.hasOwnProperty(i)) {
                                    var hours = "";
                                    for (var j in ret[i]) if (ret[i].hasOwnProperty(j))
                                        hours += "<b>" + j + "</b>: " + ret[i][j].join(",") + "<br/>";
                                    itemsField.append(
                                        '<li class="list-group-item">' +
                                        '<div class="col-xs-3 col-sm-3 col-md-3 col-lg-3 vcenter">' +
                                        i +
                                        '</div>' +
                                        '<div class="col-xs-9 col-sm-9 col-md-9 col-lg-9">' +
                                        hours +
                                        '</div>' +
                                        '</li>'
                                    );
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>
    </body>
    </html>
    <?php
}