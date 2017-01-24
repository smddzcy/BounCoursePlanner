<?php

declare (strict_types = 1);
require_once 'CurlRequest.php';

class CourseFetcher
{
    /**
     * @var string Year of the fetched courses, format: "2016", "2017" etc.
     */
    private $year;
    /**
     * @var string Semester of the fetched courses, it can be "1", "2" or "3"
     */
    private $semester;
    /**
     * @var array Holds the course programs
     */
    private $programs;
    /**
     * @var array Holds the details of the course programs
     */
    private $programDetails;
    /**
     * @var CurlRequest Holds the cURL handler
     */
    private $curlHandler;

    /**
     * If true, it also fetches the problem sessions.
     */
    const PROBLEMSESSIONS = true;
    const FILENAME_DETAILS = 'db/%s,%s-course_details.txt';
    const FILENAME_PROGRAMS = 'db/%s,%s-course_programs.txt';
    const BASEURL = 'http://registration.boun.edu.tr';
    const PROGURL = self::BASEURL.'/scripts/schdepsel.asp';

    public function __construct(int $year = null, int $semester = null)
    {
        if (!is_null($year) && !is_null($semester)) {
            $this->year = (int) str_pad((String) $year, 4, '20', STR_PAD_LEFT);
            $this->semester = $semester;
        } else {
            $m = (int) date('m');
            $s = 1;
            if ($m >= 1 && $m <= 6) {
                $s = 2;
            } elseif ($m >= 6 && $m <= 8) {
                $s = 3;
            }
            $this->year = date('Y');
            if ($s !== 1) {
                --$this->year;
            }
            $this->semester = $s;
        }
        $this->curlHandler = new CurlRequest();
        $this->curlHandler->setExtra([
            CURLOPT_HTTPHEADER => [
                'Accept-Language: tr-TR,tr;q=0.8,en-US;q=0.5,en;q=0.3',
                'Referer: http://registration.boun.edu.tr/scripts/schdepsel.asp',
            ],
            CURLOPT_TIMEOUT => 45,
        ]);
        $this->programs = [];
        $this->programDetails = [];
    }

    /**
     * Fetches the semesters from BOUN servers2.
     *
     * @var int Limit the fetched semester count
     *
     * @return array Year-semester datas as nested arrays
     */
    public function getSemesters(int $limit = 10)
    {
        $schedulePageData = $this->curlHandler->get(self::BASEURL.'/schedule.htm');
        preg_match_all("#<option[^>]*value='([^']*)'#si", $schedulePageData, $semesterDatas);
        $semesters = [];

        if (count($semesterDatas) == 0) {
            return [];
        }

        foreach (array_slice($semesterDatas[1], 0, $limit) as $semesterData) {
            $semesterData = trim($semesterData);
            $semesters[] = [
                substr($semesterData, 0, strpos($semesterData, '/')), // year
                substr($semesterData, -1), // semester
            ];
        }

        return $semesters;
    }

    /**
     * @param bool $update Fetch a new program list or not
     *
     * @return array Programs array [[Abbreviation => URL]]
     */
    public function getPrograms($update = false): array
    {
        $programsFilename = sprintf(self::FILENAME_PROGRAMS, $this->year, $this->semester);

        if ($update !== true && file_exists($programsFilename)) {
            return (array) unserialize(file_get_contents($programsFilename));
        }
        preg_match_all('#<td[^>]*class\="schtd">[^<]*<a[^>]*class\="menu2"[^>]*href\="([^"]*kisaadi\=([^\\&]*)[^"]*)#si', $this->curlHandler->get(self::PROGURL, [
            'semester' => $this->year.'/'.($this->year + 1).'-'.$this->semester,
        ]), $programs);
        array_walk($programs[1], function (&$val) {
            return $val = self::BASEURL.$val;
        });
        for ($i = 0; $i < count($programs[1]); ++$i) {
            if (!array_key_exists($i, $programs[2])) {
                $programs[2][$i] = $i;
            }
            $this->programs[$programs[2][$i]] = $programs[1][$i];
        }
        file_put_contents($programsFilename, serialize($this->programs));

        return $this->programs;
    }

    /**
     * @param string $abbr Course name abbreviation
     * @param int    $code Course code. If null, give appropriate code appended to the course name for correct results
     *
     * @return mixed [Section] [Days][Hours][Rooms][Instructor]
     */
    public function getDetails(String $abbr, int $code = null)
    {
        $this->getAllDetails();
        if (!array_key_exists($abbr.$code, $this->programDetails)) {
            return false;
        }
        if (is_null($code)) {
            return $this->programDetails[$abbr];
        }

        return $this->programDetails[$abbr.$code];
    }

    public function getAllDetails($update = false)
    {
        $detailsFilename = sprintf(self::FILENAME_DETAILS, $this->year, $this->semester);

        if ($update !== true && file_exists($detailsFilename)) {
            return $this->programDetails = (array) unserialize(file_get_contents($detailsFilename));
        }

        foreach ($this->getPrograms() as $programURL) {
            preg_match_all('#<tr[^>]*class\="schtd.?">(.*?)</tr>#si', $this->curlHandler->get($programURL), $sections);

            $lastCourseCode = null;
            $lastCourseSection = null;
            foreach ($sections[1] as $section) {
                if (self::PROBLEMSESSIONS !== true && preg_match('/P\\.S\\./si', $section)) {
                    continue;
                }

                // Code.Section, Name, Credits, Instr, Days, Hours, Classes
                preg_match('#<td>(.*?)</td>[^<]*<td>.*?</td>[^<]*<td>([^<]*)</td>[^<]*<td>([^<]*)</td>[^<]*<td>[^<]*</td>(.*?</a>)?.*?<td>([^<]*)</td>[^<]*<td>([^<]*)</td>[^<]*<td>([^<]*)</td>[^<]*<td>(.*?)</td>#si', $section, $details);
                $this->cleanArray($details, 2);
                // No hour details, skip
                if (preg_match('#TBA#si', $details[6])) {
                    continue;
                }

                if (array_key_exists(1, $details) && !empty($details[1])) {
                  list($courseCode, $courseSection) = explode('.', $this->trueTrim($details[1]));
                } else {
                  list($courseCode, $courseSection) = ["", ""];
                }
                $courseName = $this->trueTrim($details[2]);
                $courseCredit = $this->trueTrim($details[3]);
                $courseInstr = $this->trueTrim($details[5]);
                $courseDays = $this->trueTrim($details[6]);
                $courseHours = $this->trueTrim($details[7]);
                $courseRooms = $this->trueTrim($details[8]);

                // If course code is empty, then it belongs to a parent course.
                $det = null;
                if (empty($courseCode)) {
                    // Details' indices increase by 1 if courseCode is empty.
                    $courseInstr = $this->trueTrim($details[6]);
                    $courseDays = $this->trueTrim($details[7]);
                    $courseHours = $this->trueTrim($details[8]);
                    // Get the main course field, it's a LAB or P.S.
                    $det = $this->programDetails[$lastCourseCode][$lastCourseSection];
                } else {
                    if (!array_key_exists($courseCode, $this->programDetails)) {
                        $this->programDetails[$courseCode] = [$courseSection => [null, null, null, null]];
                    }
                    if (!array_key_exists($courseSection, $this->programDetails[$courseCode])) {
                        $this->programDetails[$courseCode][$courseSection] = [null, null, null, null];
                    }
                    $det = $this->programDetails[$courseCode][$courseSection];
                    $lastCourseCode = $courseCode;
                    $lastCourseSection = $courseSection;
                }

                $det[0] .= $courseDays;
                $det[1] .= $courseHours;
                if (!empty($courseRooms)) {
                    $det[2] .= empty($det[2]) ? $courseRooms : ' | '.$courseRooms;
                }
                if (!empty($courseInstr)) {
                    $det[3] .= empty($det[3]) ? $courseInstr : ' | '.$courseInstr;
                }
                // 4th index is course credit
                if (empty($det[4])) {
                    $det[4] = (int) $courseCredit;
                }
                // 5th index is full course names and instructors
                if (empty($det[5])) {
                    $det[5] = [];
                }
                $c = count(preg_split('/([A-Z][a-z]*)/', $courseDays, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
                for($i = 0; $i < $c; $i++) {
                  $det[5][] = ["fullName" => $courseName, "inst" => $courseInstr];
                }

                $this->programDetails[$lastCourseCode][$lastCourseSection] = $det;
            }
        }

        file_put_contents($detailsFilename, serialize($this->programDetails));

        return $this->programDetails;
    }

    private function cleanArray(array &$arr, $exceptKey = null)
    {
        if (!is_array($arr)) {
            return preg_replace("/\s/", '', $this->trueTrim(strip_tags($arr)));
        }

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->cleanArray($value, $exceptKey);
            } else {
                if($exceptKey !== null && $key === $exceptKey) continue;
                $arr[$key] = preg_replace("/\s/", '', $this->trueTrim(strip_tags($value)));
            }
        }

        return $arr;
    }

    /**
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param string $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * @param string $semester
     */
    public function setSemester($semester)
    {
        $this->semester = $semester;
    }

    /**
     * Clears course programs and details from memory.
     */
    public function clearCache()
    {
      $this->programs = [];
      $this->programDetails = [];
    }

    private function trueTrim($str)
    {
        return trim($str, "\xC2\xA0\n");
    }
}