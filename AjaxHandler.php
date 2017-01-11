<?php

declare (strict_types = 1);
require_once 'CourseFetcher.php';
require_once 'CoursePlanner.php';

if (!array_key_exists('function', $_POST) || !array_key_exists('data', $_POST)) {
    die();
}

$func = $_POST['function'];
$data = $_POST['data'];

// set the specific semester, if given
$year = null;
$semester = null;
if (is_array($_POST['data']) && array_key_exists('year', $_POST['data']) && array_key_exists('semester', $_POST['data']) && !empty($_POST['data']['year']) && !empty($_POST['data']['semester'])) {
    $year = (int)trim($_POST['data']['year']);
    $semester = (int)trim($_POST['data']['semester']);
}

$fetcher = new CourseFetcher($year, $semester);
$validCourseCodes = [];
$validCourses = []; // with sections
foreach ($fetcher->getAllDetails() as $courseCode => $sectionArray) {
    $sectionArray = array_keys($sectionArray);
    $validCourseCodes[] = $courseCode;
    for ($i = 0; $i < count($sectionArray); ++$i) {
        $validCourses[] = $courseCode . '.' . $sectionArray[$i];
    }
}
switch ($func) {
    case 'getSemesters':
        echo json_encode($fetcher->getSemesters());
        break;
    case 'getAvailableCourses':
        echo json_encode([$validCourseCodes, $validCourses]);
        break;
    case 'findBestPlan':
        $planner = new CoursePlanner($year, $semester);
        if (!array_key_exists('courseList', $data)) {
            break;
        }
        foreach ($data['courseList'] as $courseName) {
            if (in_array($courseName, $validCourseCodes) || in_array($courseName, $validCourses)) {
                try {
                    $planner->addCourse($courseName);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    die();
                }
            }
        }

        if (array_key_exists('freeHours', $data) && !empty($data['freeHours'])) {
            foreach ($data['freeHours'] as $unwantedHour) {
                $planner->addUnwantedHour($unwantedHour);
            }
        }

        if (array_key_exists('showDuplicates', $data)) {
            if ($data['showDuplicates'] == 'true') {
                $data['showDuplicates'] = true;
            } else {
                $data['showDuplicates'] = false;
            }
        } else {
            $data['showDuplicates'] = false;
        }

        $bestPlans = $planner->getBestPlans($data['showDuplicates']);

        foreach ($bestPlans as &$list) {
            foreach ($list as &$course) {
                $cName = $course->getName();
                $cHours = $course->getHours();
                $cCredit = $course->getCredit();
                $course = ['course-name' => $cName, 'course-hours' => $cHours,
                  'course-credit' => $cCredit];
            }
        }

        echo json_encode(['plans' => $bestPlans, 'conflict' => $planner->getConflict()]);
        break;
    case 'changeLang':
        setcookie('lang', $data, strtotime('+1 year'), '/BounCoursePlanner/', 'localhost');
        break;
}
