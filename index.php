<?php
require_once 'Language.php';
define('HOMEPAGE', 'http://www.smddzcy.com/BounCoursePlanner/');
if (!array_key_exists('lang', $_COOKIE)) {
    setcookie('lang', 'en', strtotime('+1 year'), '/BounCoursePlanner/', 'localhost');
    $userLang = 'en';
} else {
    $userLang = strtolower($_COOKIE['lang']);
}
if (!in_array($userLang, Language::VALIDLANGS)) {
    $userLang = 'en';
}
$langClass = new Language();
$langClass->setUserLanguage($userLang);
?>
<!DOCTYPE html>
<html lang="<?php echo $userLang; ?>">
<head>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo $langClass->lang['PAGE_TITLE']; ?></title>
</head>
<body>
<nav class="navbar navbar-default" role="navigation">
  <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mainNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#"><?php echo $langClass->lang['PAGE_TITLE']; ?></a>
  </div>
    <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="nav navbar-nav navbar-right social inline">
          <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span id="semesterText"><?php echo $langClass->lang['CHANGE_SEMESTER']; ?></span> <b
                      class="caret"></b></a>
              <ul class="dropdown-menu" id="semesters">
              </ul>
          </li>
          <div id="lineBreak" style="display:none">&nbsp;</div>
          <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $langClass->lang['ACTIONS']; ?> <b
                      class="caret"></b></a>
              <ul class="dropdown-menu">
                  <!-- <li><a href="#" target="_blank"><?php echo $langClass->lang['CONTRIBUTE']; ?></a></li> -->
                  <li><a href="http://www.smddzcy.com/contact/"
                         target="_blank"><?php echo $langClass->lang['CONTACT_ME']; ?></a></li>
              </ul>
          </li>
          <div id="lineBreak" style="display:none">&nbsp;</div>
            <li style="padding-left:14px !important"><p class="navbar-text"><?php echo $langClass->lang['SHARE']; ?>: </p></li>
            <li><a class="popup"
                   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(HOMEPAGE); ?>"
                   target="_blank"><i class="fa fa-lg fa-facebook"></i></a></li>
            <li><a class="popup"
                   href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode($langClass->lang['PAGE_TITLE'].' - '.$langClass->lang['SHARE_TEXT']); ?>&url=<?php echo rawurlencode(HOMEPAGE); ?>"
                   target="_blank"><i class="fa fa-lg fa-twitter"></i></a></li>
            <li><a class="popup" href="https://plus.google.com/share?url=<?php echo urlencode(HOMEPAGE); ?>"
                   target="_blank"><i class="fa fa-lg fa-google-plus"></i></a></li>
            <li><a class="popup"
                   href="https://www.tumblr.com/widgets/share/tool/preview?shareSource=legacy&canonicalUrl=&url=<?php echo urlencode(HOMEPAGE); ?>&posttype=link&title=<?php echo urlencode($langClass->lang['PAGE_TITLE']); ?>&caption=<?php echo urlencode($langClass->lang['SHARE_TEXT']); ?>&content=<?php echo urlencode(HOMEPAGE); ?>"
                   target="_blank"><i class="fa fa-lg fa-tumblr"></i></a></li>
                   <div id="lineBreak" style="display:none">&nbsp;</div>
                   <li <?php if ($userLang == 'en') { echo 'class="active"'; } ?>>
                     <a href="" class="lang" onclick="process('changeLang','en')">EN</a>
                   </li>
                   <li <?php if ($userLang == 'tr') { echo 'class="active"';} ?>>
                     <a href="" class="lang" onclick="process('changeLang','tr')">TR</a>
                   </li>
        </ul>
    </div>

</nav>
<div class="border-between">
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="page-header text-center">
            <h1>
                <small><?php echo $langClass->lang['COURSES']; ?></small>
            </h1>
        </div>
        <div class="jumbotron" style="margin-top:20px; padding-bottom: 10px;">
            <div class="container">
                <p><?php echo $langClass->lang['SLOGAN']; ?></p>

                <div id="course-form">
                    <input id="course-insert" type="text" name="course-insert"
                           placeholder="<?php echo $langClass->lang['COURSE_NAME']; ?>">
                    <button id="add-course"><?php echo $langClass->lang['ADD_COURSE']; ?></button>
                </div>
                <div id="course-container">
                    <ul id="course-list">
                        <h3 class="nothing-message"><?php echo $langClass->lang['NO_COURSES']; ?></h3>
                    </ul>
                    <div id="controls">
                        <p>*<?php echo $langClass->lang['SINGLE_CLICK']; ?></p>
                        <p>**<?php echo $langClass->lang['DOUBLE_CLICK']; ?></p>
                        <br>

                        <div>
                            <p><?php echo $langClass->lang['SHOW_DUPLICATES']; ?>:</p>
                            <input type="checkbox" id="show-duplicates"/>
                        </div>

                        <button id="clear-all-courses"
                                class="course-button"><?php echo $langClass->lang['CLEAR_COURSES']; ?></button>
                        <button id="find-best-plan"
                                class="course-button"><?php echo $langClass->lang['FIND_SCHEDULE']; ?></button>
                    </div>

                </div>

                <div class="alert alert-info" style="margin-top:5px;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <ul style="padding-left:10px;">
                        <strong><?php echo $langClass->lang['NOTE_TITLE']; ?></strong>
                        <li><?php echo $langClass->lang['NOTE_1']; ?></li>
                        <li><?php echo $langClass->lang['NOTE_2']; ?></li>
                        <li><?php echo $langClass->lang['NOTE_3']; ?></li>
                        <li><?php echo $langClass->lang['NOTE_4']; ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="page-header text-center">
            <h1>
                <small><?php echo $langClass->lang['SCHEDULE']; ?></small>
            </h1>
        </div>
        <div id="day-schedule" style="margin-top:5px;"></div>
        <br/>

        <ol class="breadcrumb text-center hidden" id="breadcrumb">
            <ul class="pagination pagination-lg" style="margin:5px 0;">
            </ul>
            <h4>
                <p style="float:left;margin-left:5%;"><?php echo $langClass->lang['POSSIBLE_SCHEDULES']; ?>: <strong
                        id="possible-schedules"></strong></p>
                <p style="float:right;margin-right:5%;"><?php echo $langClass->lang['CONFLICTED_HOURS']; ?>: <strong
                        id="conflicted-hours"></strong></p>
            </h4>
            <div class="clearfix"></div>
        </ol>


    </div>

</div>

<script type="text/javascript">
    var COURSE_ALREADY_ADDED = "<?php echo $langClass->lang['COURSE_ALREADY_ADDED']; ?>";
    var COURSE_NOT_VALID = "<?php echo $langClass->lang['COURSE_NOT_VALID']; ?>";
    var COURSE_EMPTY = "<?php echo $langClass->lang['COURSE_EMPTY']; ?>";
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/js/bootstrap-checkbox.min.js"></script>
<script type="text/javascript" src="assets/js/social-share-kit.min.js"></script>
<script type="text/javascript" src="assets/js/schedule.js"></script>
<script type="text/javascript">
    (function ($) {
        $("#day-schedule").dayScheduleSelector({
            days: [0, 1, 2, 3, 4, 5],
            stringDays: [
                <?php echo
            '"', $langClass->lang['SCHEDULE_DAY_0'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_1'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_2'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_3'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_4'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_5'], '",',
            '"', $langClass->lang['SCHEDULE_DAY_6'], '"';
                ?>
            ]
        });
    })($);
</script>
<script type="text/javascript" src="assets/js/main.js"></script>
<!--
<script type="text/javascript">
    SocialShareKit.init({
        selector: '.fa',
        url: 'http://www.smddzcy.com/BounCoursePlanner',
        text: 'Plan your courses easily'
    });
</script> -->
<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-43755905-4', 'auto');
    ga('send', 'pageview');

</script>
</body>
</html>
