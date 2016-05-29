<?php
declare(strict_types = 1);
require_once "CurlRequest.php";

class CourselistMaker
{
    const COOKIE_FILE = __DIR__ . "/tmp/boun.ckk";
    const DELAY = 1; // delay between page refreshes when they can't be reached, in seconds

    private $curlHandler;
    private $loggedIn = false;
    private $currCourses = [];

    public function __construct()
    {
        $this->curlHandler = new CurlRequest(null, ["cookie" => self::COOKIE_FILE]);
        $this->curlHandler->setExtra([
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: tr-TR,tr;q=0.8,en-US;q=0.5,en;q=0.3",
                "Connection: keep-alive",
                "Accept-Encoding: keep-alive"
            ],
            CURLOPT_FOLLOWLOCATION => true
        ]);
    }

    /**
     * @param string|int $userid Student number
     * @param string|int $pass Password for registration.boun.edu.tr
     */
    public function login($userid, $pass)
    {
        while (($loginResponse = $this->curlHandler->get("https://registration.boun.edu.tr/scripts/loginst.asp", [
                "user_id" => $userid,
                "user_pass" => $pass
            ])) === false) {
            echo "- Couldn't reach the login page, trying again." . PHP_EOL;
            $this->sleep();
        }
        if(preg_match("#30 seconds#si", $loginResponse)){
            echo "- Waiting 30 seconds to login". PHP_EOL;
            sleep(30);
            $this->login($userid, $pass);
            return;
        }
        else if (preg_match("#(Invalid login)|(Wrong User)#si", $loginResponse)) {
            echo "- Invalid user ID & password combination." . PHP_EOL;
            return;
        }
        echo "+ Successfully logged in." . PHP_EOL;
        $this->loggedIn = true;
    }

    private function sleep()
    {
        usleep(self::DELAY * 1000000);
    }

    private function checkLoginStatus()
    {
        if ($this->loggedIn !== true) {
            echo "- You must login before adding courses." . PHP_EOL;
            die();
        }
    }

    /**
     * A single course or multiple courses in an array are both acceptable as parameters.
     *
     * @param mixed $courseAbbr Course abbreviation (e.g. CMPE, MATH)
     * @param mixed $courseCode Course code (e.g. 101,150)
     * @param mixed $section Course secttion (e.g. 01,02)
     * @param array|bool $nc Non-credit(true) or credit(false)
     * @return string Response message from server
     */
    public function addCourse($courseAbbr, $courseCode, $section, $nc = false)
    {
        $this->checkLoginStatus();
        if (!is_array($courseAbbr))
            $courseAbbr = (array)$courseAbbr;
        if (!is_array($courseCode))
            $courseCode = (array)$courseCode;
        if (!is_array($section))
            $section = (array)$section;
        if (!is_array($nc))
            $nc = (array)$nc;

        $postData = [
            "D1" => "REQUIRED COURSE",
            "B1" => "Quick Add",
            "formaction" => ""
        ];

        for ($i = 1; $i < 8; ++$i) {
            $postData["abbr" . $i] = array_key_exists($i - 1, $courseAbbr) ? $courseAbbr[$i - 1] : "";
            $postData["code" . $i] = array_key_exists($i - 1, $courseCode) ? $courseCode[$i - 1] : "";
            $postData["section" . $i] = array_key_exists($i - 1, $section) ? $section[$i - 1] : "01";
            $postData["rnc" . $i] = array_key_exists($i - 1, $nc) ? ($nc[$i - 1] === true ? "NC" : "N") : "N";
            $postData["rcourse" . $i] = "";
        }

        while (($addCourseResponse = $this->curlHandler->get("https://registration.boun.edu.tr/scripts/studentaction.asp", $postData, [
                CURLOPT_REFERER => "https://registration.boun.edu.tr/scripts/student.asp"
            ])) === false) {
            echo "- Couldn't reach the course list preparation page, trying again." . PHP_EOL;
            $this->sleep();
        }

        preg_match("#Add[^-]*Drop[^-]*Action[^-]*Warnings[^-]*Start[^>]*>(.*?)<!---#si", $addCourseResponse, $responseWarnings);
        while(!array_key_exists(1, $responseWarnings)){
        	while (($addCourseResponse = $this->curlHandler->get("https://registration.boun.edu.tr/scripts/studentaction.asp", $postData, [
                CURLOPT_REFERER => "https://registration.boun.edu.tr/scripts/student.asp"
            ])) === false) {
            	echo "- Couldn't reach the course list preparation page, trying again." . PHP_EOL;
            	$this->sleep();
        	}
        	preg_match("#Add[^-]*Drop[^-]*Action[^-]*Warnings[^-]*Start[^>]*>(.*?)<!---#si", $addCourseResponse, $responseWarnings);
        }
        echo "+ Course add request is sent, your response:" . PHP_EOL;
        $responseWarnings = preg_replace("/\s+/", " ", strip_tags($responseWarnings[1]));
        echo $responseWarnings . PHP_EOL;
        return $responseWarnings;
    }

    public function sendConsent($courseAbbr, $courseCode, $section, $msg)
    {
        if (strlen($section) == 1) $section = "0" . $section;
        if (strlen($msg) > 400) {
            echo "- Your message is too long, it can have a max. of 400 characters." . PHP_EOL;
            return;
        }
        $this->checkLoginStatus();
        $this->curlHandler->setExtra([
            CURLOPT_REFERER => "https://registration.boun.edu.tr/scripts/stuconsent.asp"
        ]);
        while (($consentResponse = $this->curlHandler->get("https://registration.boun.edu.tr/scripts/stuconsent.asp", [
                "action" => "add",
                "courseabbr" => "",
                "coursename" => "{$courseAbbr} {$courseCode}.{$section}",
                "comment" => $msg,
                "remLen2" => (400 - strlen($msg))
            ])) === false) {
            echo "- Couldn't reach the consent page, trying again." . PHP_EOL;
        }
        $this->curlHandler->setExtra([
            CURLOPT_REFERER => null
        ]);
        echo "+ Consent request is sent." . PHP_EOL;

    }

    public function getCurrentCourses()
    {
        $this->checkLoginStatus();
        while (($stdPage = $this->curlHandler->get("https://registration.boun.edu.tr/scripts/student.asp")) === false) {
            echo "- Couldn't reach the student page, trying again." . PHP_EOL;
            $this->sleep();
        }
        echo "+ Student page is opened." . PHP_EOL;
        if (!preg_match_all('!<input[^>]*type="radio"[^>]*name="R1"[^>]*value="([^"]*)">[^<]*</td>.*?<a[^>]*href="#"[^>]*courseDesc[^>]*>([^<]*)</a>(.*?)</tr>!si', $stdPage, $courseData)) {
            echo "- This shouldn't happen, something really bad is happened. Check the regexp." . PHP_EOL;
            die();
        }
        for ($i = 0; $i < count($courseData[1]); ++$i) {
            preg_match("#.*<div[^>]*>([^<]*)<#si", $courseData[3][$i], $courseHours);
            $courseHours = $courseHours[1];
            $t = explode(".", $courseData[1][$i]);
            array_walk($t, function (&$v) {
                return $v = preg_replace("/\s/", null, $v);
            });
            $this->currCourses[$i] = [
                "name" => $t[0] . "." . $t[1],
                "description" => $courseData[2][$i],
                "credit" => $t[2],
                "hours" => $courseHours
            ];
        }
        $this->displayCurrentCourses();
    }

    public function displayCurrentCourses()
    {
        if (empty($this->currCourses)){
            $this->getCurrentCourses();
            return;
        }
        echo "+ Here are your courses: " . PHP_EOL;
        foreach ($this->currCourses as $course) {
            echo $course["name"] . " - " . $course["description"] . " - " . $course["credit"] . " - " . $course["hours"] . PHP_EOL;
        }
    }

    public function __destruct()
    {
        @unlink(self::COOKIE_FILE);
    }


}
