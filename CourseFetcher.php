<?php
declare(strict_types = 1);
require_once "CurlRequest.php";

class CourseFetcher
{
    private $year;
    private $semester;
    private $programs;
    private $programDetails;
    private $curlHandler;

    const PROBLEMSESSIONS = false;
    const FILENAME_DETAILS = "db/course_details.txt";
    const FILENAME_PROGRAMS = "db/course_programs.txt";
    const BASEURL = "http://registration.boun.edu.tr";
    CONST PROGURL = self::BASEURL . "/scripts/schdepsel.asp";

    public function __construct(int $year = null, int $semester = null)
    {
        if (!is_null($year) && !is_null($semester)) {
            $this->year = (int)str_pad((String)$year, 4, "20", STR_PAD_LEFT);
            $this->semester = $semester;
        } else {
            $m = (int)date('m');
            $s = 1;
            if ($m >= 1 && $m <= 6)
                $s = 2;
            else if ($m >= 6 && $m <= 8)
                $s = 3;
            $this->year = date('Y');
            if ($s !== 1) $this->year--;
            $this->semester = $s;
        }
        $this->curlHandler = new CurlRequest();
        $this->curlHandler->setExtra([
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_VERBOSE => false,
            CURLOPT_HTTPHEADER => [
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: tr-TR,tr;q=0.8,en-US;q=0.5,en;q=0.3",
                "Referer: http://registration.boun.edu.tr/scripts/schdepsel.asp"
            ],
            CURLOPT_TIMEOUT => 45
        ]);
        $this->programs = [];
        $this->programDetails = [];
    }

    /**
     * @param boolean $update Fetch a new program list or not
     * @return array Programs array [[Abbreviation => URL]]
     */
    public function getPrograms($update = false): array
    {
        if ($update !== true && file_exists(self::FILENAME_PROGRAMS)) return (array)unserialize(file_get_contents(self::FILENAME_PROGRAMS));
        preg_match_all('#<td[^>]*class\="schtd">[^<]*<a[^>]*class\="menu2"[^>]*href\="([^"]*kisaadi\=([^\\&]*)[^"]*)#si', $this->curlHandler->get(self::PROGURL, [
            "semester" => $this->year . "/" . ($this->year + 1) . "-" . $this->semester
        ]), $programs);
        array_walk($programs[1], function (&$val) {
            return $val = self::BASEURL . $val;
        });
        for ($i = 0; $i < count($programs[1]); ++$i) {
            if (!array_key_exists($i, $programs[2])) $programs[2][$i] = $i;
            $this->programs[$programs[2][$i]] = $programs[1][$i];
        }
        file_put_contents(self::FILENAME_PROGRAMS, serialize($this->programs));
        return $this->programs;
    }

    /**
     * @param String $abbr Course name abbreviation
     * @param int $code Course code. If null, give appropriate code appended to the course name for correct results
     * @return mixed [Section] [Days][Hours][Rooms][Instructor]
     */
    public function getDetails(String $abbr, int $code = null)
    {
        $this->getAllDetails();
        if (!array_key_exists($abbr . $code, $this->programDetails)) return false;
        if (is_null($code)) return $this->programDetails[$abbr];
        return $this->programDetails[$abbr . $code];
    }

    public function getAllDetails($update = false)
    {
        if ($update !== true && file_exists(self::FILENAME_DETAILS)) {
            return $this->programDetails = (array)unserialize(file_get_contents(self::FILENAME_DETAILS));
        }
        foreach ($this->getPrograms() as $programURL) {
            preg_match_all('#<tr[^>]*class\="schtd.?">(.*?)</tr>#si', $this->curlHandler->get($programURL), $sections);
            foreach ($sections[1] as $section) {
                if (self::PROBLEMSESSIONS === true && preg_match('/P\\.S\\./si', $section)) continue;
                // [1]: Instr. [2]: Days [3]: Hours [4]: Classes [5]: Codes.Sections
                preg_match('#.*<td>(.*?)</td>[^<]*<td>(.*?)</td>[^<]*<td>(.*?)</td>[^<]*<td>(.*?)</td>[^<]*<td>[^<]*</td>[^<]*<td>[^<]*</td>[^<]*<td>[^<]*</td>[^<]*<td>(.*?)</td>#si', $section, $details);
                $this->cleanArray($details);
                if (preg_match("#TBA#si", $details[2])) continue;
                list($courseCode, $courseSection) = explode(".", $details[5]);
                $rooms = trim($details[4], "\xC2\xA0\n");
                $hours = trim($details[3], "\xC2\xA0\n"); // no room info
                $det =& $this->programDetails[$courseCode][$courseSection];
                if (empty($det)) $det = [null, null, null, null];
                $det[0] .= $details[2];
                $det[1] .= $hours;
                if ($rooms != "")
                    $det[2] .= empty($det[2]) ? $rooms : " | " . $rooms;
                if ($details[1] != "")
                    $det[3] .= empty($det[3]) ? $details[1] : " | " . $details[1];
            }
        }
        file_put_contents(self::FILENAME_DETAILS, serialize($this->programDetails));
        return $this->programDetails;
    }

    private function cleanArray(array &$arr)
    {
        if (!is_array($arr)) return preg_replace("/\s/", "", trim(strip_tags($arr), "\xC2\xA0\n"));

        foreach ($arr as $key => $value) {
            if (is_array($value)) $arr[$key] = $this->cleanArray($value);
            else $arr[$key] = preg_replace("/\s/", "", trim(strip_tags($value), "\xC2\xA0\n"));
        }
        return $arr;
    }
}
