<?php
if (array_key_exists('func', $_POST))
    {
    if ($_POST['func'] === 'ajaxSetAdvent') {
        $day = $_POST['day'];

        $result = ajaxSetAdvent($day);
        echo(json_encode($result));
        exit();
        }
    }

// check for existance of advent db
if (!checkAdventDBExists()) {
    echo ("Advent DB does not exist. Please contact SAX.");
    exit();
}

$rows = buildCalendar();

function buildCalendar() {
    $days = 25;
    $rows = [];
    $days_data = "";

    $December = DateTime::createFromFormat('Y-m-d', "2024-12-1");
    // iterate through and create the table data for the rows of the calendar
    for ($i = 1; $i <= $days; $i++) {
        $checked = "";
        $date = $December->format('Y-m-d');
        $advent = getAdvent($date);
        $url = isset($advent['url']) ? $advent['url'] : '';
        $checked = $advent['checked'];
        $days_data .= 
        <<<HTML
        <td class="closed">
            <input type="hidden" class="advent_day" value="{$date}">
            <div id=box>
                <label>
                    <input type='checkbox' class="adventCheckbox" $checked>
                    <div class='door'>
                        <div class='front'>{$i}</div>
                        <div class='back' style="background: url('{$url}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                    </div>
                </label>
            </div>
        </td>
        HTML;
        if ($i % 5 === 0 && $i !== 0) {
            $days_row = "<tr>{$days_data}</tr>";
            $rows[] = $days_row;
            $days_data = "";
        }

        $December->modify("+1 days");
    }

    return $rows;
}

function getAdvent($date) {
    $db = new SQLite3('advent_calendar.sqlite', SQLITE3_OPEN_READWRITE);

    $statement = $db->prepare(
        'SELECT
            *
        FROM
            collection
        WHERE
            collection.day = :day'
        );

    $statement->bindValue('day', $date);

    $result = $statement->execute();
    if ($result === false) {
        echo("Failure querying advents.");
    }

    $res_array = $result->fetchArray(SQLITE3_ASSOC);
    if ($res_array) {
        return [
            'checked' => 'checked',
            'url' => $res_array['file_location'],
            ];
    }
    
    return [
        'checked' => '',
        'url' => '',
    ];
}

function checkFile($file) {
    $db = new SQLite3('advent_calendar.sqlite', SQLITE3_OPEN_READWRITE);

    $statement = $db->prepare(
        'SELECT
            *
        FROM
            collection
        WHERE
            collection.file_location = :file'
        );

    $statement->bindValue('file', $file);

    $result = $statement->execute();
    if ($result === false) {
        echo("Failure querying advents.");
    }

    $res_array = $result->fetchArray(SQLITE3_ASSOC);
    if (!empty($res_array)) {
        return true;
    }

    return false;
}

function ajaxSetAdvent($date) {

    // first check for existance of "database"
    if (!checkAdventDBExists()) {
        $ret = [
            'error' => "Database doesn't exist.",
            'success' => false,
        ];
        return $ret;
    }

    // set user timezone
    if (isset($_POST['timezone'])) {
        $timezone = $_POST['timezone'];
        // set timezone for server
        date_default_timezone_set($timezone);
    }
  
    // then check the date. If the date chosen is not today's date, then don't set advent.
    $ChosenDate = DateTime::createFromFormat('Y-m-d', "{$date}");
    $chosen_date_string = $ChosenDate->format('Y-m-d');
    $todays_date = date('Y-m-d');
    if (!(strtotime($chosen_date_string) <= strtotime($todays_date))) {
        $ret = [
            'error' => "Date chosen is not today's date. You can only pick an advant that is today's date or before todays date.",
            'success' => false,
            'type' => 'invalid_date',
        ];
        return $ret;
    }

    // pick a file at random
    $files = glob('images/*.*');
    $file_index = array_rand($files);
    $file = $files[$file_index];

    $advent = checkFile($file);
    // if value is checked, then the image has been stored in the db already
    while ($advent) {
        $file_index = array_rand($files);
        $file = $files[$file_index];
        $advent = checkFile($file);
    }

    $db = new SQLite3('advent_calendar.sqlite', SQLITE3_OPEN_READWRITE);

    $statement = $db->prepare(
        'INSERT INTO "collection" ("file_location", "day")
        VALUES (:file_location, :day)'
        );

    $statement->bindValue("file_location", $file);
    $statement->bindValue("day", $chosen_date_string);

    $result = $statement->execute();

    if (!$result) {
        $ret = [
        'error' => "Error with setting up query to store advent.",
        'file_location' => $file,
        'date' => $date,
        'success' => false,
        ];
        return $ret;
    }

    if (!$result->finalize())
        {
        $ret = [
            'error' => "Error setting advent in database.",
            'file_location' => $file,
            'date' => $date,
            'success' => false,
        ];
        return $ret;
        }

    $ret = [
        'success' => true,
        'file_location' => $file,
    ];

    return $ret;
}

function checkAdventDBExists() {
    if (!file_exists('advent_calendar.sqlite')) {
       return false;
    }

    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shenmue Advent Calendar</title>
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
</head>
<style>
#box {
    height: 10rem;
    width: 10rem;
}

input[type=checkbox] {
    display:none;
}

label {
  perspective: 1000px;
  transform-style: preserve-3d;
  cursor: pointer;

  display: flex;
  min-height: 100%;
  width: 100%;
  height: 120px;
}

.door {
  width: 100%;
  transform-style: preserve-3d;
  transition: all 300ms;
  border: 2px dashed transparent;
  border-radius: 10px;
}

.front{
    width: 100% !important;
    height: 100% !important;
}

.door div {
    position: absolute;
    width: 10px;
    height: 10px;
    backface-visibility: hidden;

    border-radius: 6px;

    display: flex;
    align-items: center;
    justify-content: center;

    /* typography */
    /* font-family: 'Kalam', cursive; */
    color: #385052;
    font-size: 2em;
    font-weight: bold;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.2);
}

.door .back {
    width: 100%;
    height: 100%;
    background-size: contain !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    transform: rotateY(180deg);
}

.back {
    background-color: rgba(46, 49, 61, 0.75) !important;
}

label:hover .door {
    border-color: #385052;
}

#advent-calendar {
    background-color: rgba(255, 255, 75, 0.75);
    border: black 10px solid;
}

body {
    background-image: url('xmas-sale-banner.jpg');
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    /* background-size: cover; */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
}

td {
    border: 3px solid grey;
    border-radius: 10px;
}

#banner {
    text-align: center;
    font-size: 2rem;
    font-family: mistral;
}

#banner h1 {
    margin-top: 0px;
    margin-bottom: 0px;
    color: #3f2d20;
    font-weight: bold;
    -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: #b9b0a7;
}

h3 {
    text-align: center;
    font-size: 2rem;
    font-family: mistral;
    color: #3f2d20;
    font-weight: bold;
    -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: #b9b0a7;
}

@font-face {
    font-family: "mistral";
    src: url("mistral.ttf");
}
</style>
<!-- SCM Music Player https://www.scmplayer.net -->
<script type="text/javascript" src="https://www.scmplayer.net/script.js" 
data-config="{'skin':'skins/tunes/skin.css','volume':59,'autoplay':true,'shuffle':true,'repeat':1,'placement':'top','showplaylist':false,'playlist':[{'title':'Shenmue Theme','url':'https://vgmsite.com/soundtracks/shenmue-shenhua-1998/xxnqnutrww/01. Shenmue Main Theme.flac'},{'title':'Sedge Flower - Shenhua','url':'https://vgmsite.com/soundtracks/shenmue-original-soundtrack-lp-2015/idxltzovtr/A2%20Sedge%20Flower-Shenfa.flac'},{'title':'Christmas On Dubuita Street','url':'https://vgmsite.com/soundtracks/shenmue-original-soundtrack-lp-2015/bhtbbyenaj/A4%20Christmas%20On%20Dobuita%20Street-Kurisumasuno%20Dobuitadoori.flac'},{'title':'Tomato Mart Theme','url':'https://vgmsite.com/soundtracks/shenmue-1999-dreamcast-gamerip/kaobzxriea/010.flac'}]}" ></script>
<!-- SCM Music Player script end -->



<script>
    $(document).ready(function(){
        let checked = $("input[type='checkbox']:checked");
        checked.each(function(){
            let door = $(this).closest('td').find('.door');
            $(door).css('transform', 'rotateY(180deg)');
            $(this).attr('disabled', true);
        });

        $(".door").on('click', function() {
                let day = $(this).closest('td').find('.advent_day').val();
                let ele = $(this);
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                let data = {
                    func: 'ajaxSetAdvent',
                    day: day,
                    timezone: timezone,
            };
            $.post('index.php', data, function(res){
                res = JSON.parse(res);
                if (res.success == false) {
                    console.error(res.error);
                    if (res.type === "invalid_date") {
                        alert(res.error);
                    }
                }
                else {
                    let checkbox = ele.closest('td').find(':checkbox');
                    if (checkbox.is(':checked')) {
                        ele.css('transform', 'rotateY(180deg)');
                        let back = ele.find('.back');
                        back.css('background', 'url(' + res.file_location + ')');
                        back.css('background-repeat', 'no-repeat');
                        back.css('background-position', 'center');
                        back.css('background-size', 'contain');
                        ele.closest('td').find(':checkbox').attr('disabled', true);
                        $("#shimmer")[0].play();
                        }
                }
            });
        });
    });
</script>
<body>
    <div id="banner">
        <h1>Yu Suzuki's* Shenmue Advent Calender</h1>
    </div>
    <div id="advent-calendar">
        <table>
        <?php
            foreach ($rows as $data) {
                echo($data);
            }
            ?>
        </table>
    </div>
    <div>
        <h3>(*not affiliated with Yu Suzuki)</h3>
    </div>
    <div style="display:none">
    <audio id="shimmer" src="qte-success.mp3"></audio>
    </div>
</html>