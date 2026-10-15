<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF, ROLE_ADMIN]);

$userRole = getCurrentUserRole();
$isAdmin = ($userRole === ROLE_ADMIN);
$db = getDB();

// Determine staff member whose timetable is being viewed
$allStaff = [];
if ($isAdmin) {
    $allStaff = $db->query("
        SELECT st.id, st.staff_code, st.first_name, st.last_name, d.code AS dept_code
        FROM staff st
        JOIN departments d ON st.department_id = d.id
        ORDER BY st.first_name ASC
    ")->fetchAll();
    $currentStaffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : (isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : ($allStaff[0]['id'] ?? 0));
} else {
    $currentStaffId = $_SESSION['role_id'] ?? 0;
}

// Fetch current staff details
$currentStaff = null;
if ($currentStaffId > 0) {
    $stfStmt = $db->prepare("
        SELECT s.*, d.name AS dept_name, d.code AS dept_code 
        FROM staff s 
        JOIN departments d ON s.department_id = d.id 
        WHERE s.id = ?
    ");
    $stfStmt->execute([$currentStaffId]);
    $currentStaff = $stfStmt->fetch();
}

// Helper to output native Excel (.xlsx) file using ZipArchive (no warnings, 100% Excel compatible)
function outputTimetableXlsx($grid, $days, $filename) {
    if (ob_get_level()) {
        ob_end_clean();
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'tt_xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
'<Default Extension="xml" ContentType="application/xml"/>' .
'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
'</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
'</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
'</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
'<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="22000" windowHeight="12000"/></bookViews>' .
'<sheets><sheet name="Timetable" sheetId="1" r:id="rId1"/></sheets>' .
'</workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
'<fonts count="5">' .
  '<font><sz val="10"/><name val="Segoe UI"/><color rgb="FF000000"/></font>' .
  '<font><b/><sz val="10"/><name val="Segoe UI"/><color rgb="FFFFFFFF"/></font>' .
  '<font><b/><sz val="10.5"/><name val="Segoe UI"/><color rgb="FF0F344D"/></font>' .
  '<font><b/><sz val="11"/><name val="Segoe UI"/><color rgb="FFB45309"/></font>' .
  '<font><b/><sz val="11"/><name val="Segoe UI"/><color rgb="FF1E3A8A"/></font>' .
'</fonts>' .
'<fills count="5">' .
  '<fill><patternFill patternType="none"/></fill>' .
  '<fill><patternFill patternType="gray125"/></fill>' .
  '<fill><patternFill patternType="solid"><fgColor rgb="FF133B5C"/></patternFill></fill>' .
  '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF2DB"/></patternFill></fill>' .
  '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F0FE"/></patternFill></fill>' .
'</fills>' .
'<borders count="2">' .
  '<border><left/><right/><top/><bottom/><diagonal/></border>' .
  '<border>' .
    '<left style="thin"><color rgb="FFD1D5DB"/></left>' .
    '<right style="thin"><color rgb="FFD1D5DB"/></right>' .
    '<top style="thin"><color rgb="FFD1D5DB"/></top>' .
    '<bottom style="thin"><color rgb="FFD1D5DB"/></bottom>' .
  '</border>' .
'</borders>' .
'<cellStyleXfs count="1">' .
  '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
'</cellStyleXfs>' .
'<cellXfs count="7">' .
  '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
  '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
  '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
  '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
  '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
  '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
  '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
'</cellXfs>' .
'</styleSheet>';
    $zip->addFromString('xl/styles.xml', $styles);

    $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
    $headers = [
        'Days',
        "08.30 to 09.30",
        "09.30 to 10.30",
        "10.40 to 11.40",
        "11.40 to 12.40",
        "12.40 to 01.30",
        "01.30 to 02.30",
        "02.30 to 03.30",
        "03.30 to 04.30"
    ];

    $lunchLetters = [
        'Monday' => 'L',
        'Tuesday' => 'U',
        'Wednesday' => 'N',
        'Thursday' => 'C',
        'Friday' => 'H',
        'Saturday' => '-'
    ];

    $mergeRanges = [];
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
'<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" ySplit="1" topLeftCell="A2" activePane="bottomLeft"/></sheetView></sheetViews>' .
'<sheetFormatPr defaultRowHeight="25"/>' .
'<cols>' .
  '<col min="1" max="1" width="14" customWidth="1"/>' .
  '<col min="2" max="5" width="16" customWidth="1"/>' .
  '<col min="6" max="6" width="11" customWidth="1"/>' .
  '<col min="7" max="9" width="16" customWidth="1"/>' .
'</cols>' .
'<sheetData>';

    $sheetXml .= '<row r="1" ht="32" customHeight="1">';
    foreach ($headers as $idx => $h) {
        $cellRef = $colLetters[$idx] . '1';
        $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($h, ENT_XML1) . '</t></is></c>';
    }
    $sheetXml .= '</row>';

    $rowNum = 2;
    foreach ($days as $day) {
        $sheetXml .= '<row r="' . $rowNum . '" ht="52" customHeight="1">';
        $sheetXml .= '<c r="A' . $rowNum . '" s="2" t="inlineStr"><is><t>' . $day . '</t></is></c>';

        $p = 1;
        while ($p <= 7) {
            if ($p == 5) {
                $lChar = $lunchLetters[$day] ?? 'L';
                $sheetXml .= '<c r="F' . $rowNum . '" s="3" t="inlineStr"><is><t>' . $lChar . '</t></is></c>';
            }

            $colIdx = ($p <= 4) ? $p : ($p + 1);

            if (isset($grid[$day][$p])) {
                $slot = $grid[$day][$p];
                $maxP = ($p <= 4) ? 4 : 7;
                $span = 1;
                for ($nextP = $p + 1; $nextP <= $maxP; $nextP++) {
                    if (isset($grid[$day][$nextP]) && 
                        ($grid[$day][$nextP]['subject_code'] ?? '') === ($slot['subject_code'] ?? '') && 
                        ($grid[$day][$nextP]['room_number'] ?? '') === ($slot['room_number'] ?? '')) {
                        $span++;
                    } else {
                        break;
                    }
                }

                $cellRef = $colLetters[$colIdx] . $rowNum;
                $textVal = $slot['subject_code'];
                if (!empty($slot['room_number'])) {
                    $textVal .= "\n(" . $slot['room_number'] . ")";
                }

                if ($span > 1) {
                    $endColIdx = $colIdx + $span - 1;
                    $mergeRanges[] = $colLetters[$colIdx] . $rowNum . ':' . $colLetters[$endColIdx] . $rowNum;
                    $sheetXml .= '<c r="' . $cellRef . '" s="5" t="inlineStr"><is><t>' . htmlspecialchars($textVal, ENT_XML1) . '</t></is></c>';
                    for ($covered = 1; $covered < $span; $covered++) {
                        $cRef = $colLetters[$colIdx + $covered] . $rowNum;
                        $sheetXml .= '<c r="' . $cRef . '" s="5"/>';
                    }
                    $p += $span;
                } else {
                    $sheetXml .= '<c r="' . $cellRef . '" s="4" t="inlineStr"><is><t>' . htmlspecialchars($textVal, ENT_XML1) . '</t></is></c>';
                    $p++;
                }
            } else {
                $cellRef = $colLetters[$colIdx] . $rowNum;
                $sheetXml .= '<c r="' . $cellRef . '" s="6"/>';
                $p++;
            }
        }

        $sheetXml .= '</row>';
        $rowNum++;
    }

    $sheetXml .= '</sheetData>';

    if (!empty($mergeRanges)) {
        $sheetXml .= '<mergeCells count="' . count($mergeRanges) . '">';
        foreach ($mergeRanges as $mr) {
            $sheetXml .= '<mergeCell ref="' . $mr . '"/>';
        }
        $sheetXml .= '</mergeCells>';
    }

    $sheetXml .= '</worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tempFile);
    @unlink($tempFile);
    exit;
}

// Handle Download Model Excel Template (.xlsx) — Image Model Format
if (isset($_GET['action']) && in_array($_GET['action'], ['download_model_excel', 'download_model_xlsx'])) {
    $modelGrid = [
        'Monday' => [
            2 => ['subject_code' => 'A', 'room_number' => '2316'],
            3 => ['subject_code' => 'C', 'room_number' => '2215'],
            4 => ['subject_code' => 'B', 'room_number' => '2215'],
            5 => ['subject_code' => 'G', 'room_number' => ''],
            6 => ['subject_code' => 'G', 'room_number' => ''],
            7 => ['subject_code' => 'G', 'room_number' => ''],
        ],
        'Tuesday' => [
            3 => ['subject_code' => 'B', 'room_number' => '2317'],
            4 => ['subject_code' => 'C', 'room_number' => '2213'],
            5 => ['subject_code' => 'A', 'room_number' => '2211'],
            6 => ['subject_code' => 'D', 'room_number' => '2211'],
        ],
        'Wednesday' => [
            2 => ['subject_code' => 'C', 'room_number' => '2211'],
            3 => ['subject_code' => 'E', 'room_number' => '2214'],
            4 => ['subject_code' => 'B', 'room_number' => '2214'],
        ],
        'Thursday' => [
            2 => ['subject_code' => 'E', 'room_number' => '2213'],
            3 => ['subject_code' => 'D', 'room_number' => '2213'],
            4 => ['subject_code' => 'A', 'room_number' => '2213'],
            5 => ['subject_code' => 'P&T', 'room_number' => ''],
            6 => ['subject_code' => 'P&T', 'room_number' => ''],
            7 => ['subject_code' => 'P&T', 'room_number' => ''],
        ],
        'Friday' => [
            3 => ['subject_code' => 'D', 'room_number' => '2318'],
            4 => ['subject_code' => 'E', 'room_number' => '2318'],
        ]
    ];
    outputTimetableXlsx($modelGrid, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'Timetable_Model_Template.xlsx');

}

// Handle 1-Click Load Image Model Schedule into Database for Current Staff
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'load_model_schedule') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Invalid security token. Please try again.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    $modelGrid = [
        'Monday' => [
            2 => ['code' => 'A', 'name' => 'Advanced Algorithms', 'room' => '2316'],
            3 => ['code' => 'C', 'name' => 'Computer Networks', 'room' => '2215'],
            4 => ['code' => 'B', 'name' => 'Cloud Computing', 'room' => '2215'],
            5 => ['code' => 'G', 'name' => 'General / Lab Practical', 'room' => ''],
            6 => ['code' => 'G', 'name' => 'General / Lab Practical', 'room' => ''],
            7 => ['code' => 'G', 'name' => 'General / Lab Practical', 'room' => ''],
        ],
        'Tuesday' => [
            3 => ['code' => 'B', 'name' => 'Cloud Computing', 'room' => '2317'],
            4 => ['code' => 'C', 'name' => 'Computer Networks', 'room' => '2213'],
            5 => ['code' => 'A', 'name' => 'Advanced Algorithms', 'room' => '2211'],
            6 => ['code' => 'D', 'name' => 'Database Systems', 'room' => '2211'],
        ],
        'Wednesday' => [
            2 => ['code' => 'C', 'name' => 'Computer Networks', 'room' => '2211'],
            3 => ['code' => 'E', 'name' => 'Software Engineering', 'room' => '2214'],
            4 => ['code' => 'B', 'name' => 'Cloud Computing', 'room' => '2214'],
        ],
        'Thursday' => [
            2 => ['code' => 'E', 'name' => 'Software Engineering', 'room' => '2213'],
            3 => ['code' => 'D', 'name' => 'Database Systems', 'room' => '2213'],
            4 => ['code' => 'A', 'name' => 'Advanced Algorithms', 'room' => '2213'],
            5 => ['code' => 'P&T', 'name' => 'Placement & Training', 'room' => ''],
            6 => ['code' => 'P&T', 'name' => 'Placement & Training', 'room' => ''],
            7 => ['code' => 'P&T', 'name' => 'Placement & Training', 'room' => ''],
        ],
        'Friday' => [
            3 => ['code' => 'D', 'name' => 'Database Systems', 'room' => '2318'],
            4 => ['code' => 'E', 'name' => 'Software Engineering', 'room' => '2318'],
        ]
    ];

    $periodTimes = [
        1 => ['08:30:00', '09:30:00'],
        2 => ['09:30:00', '10:30:00'],
        3 => ['10:40:00', '11:40:00'],
        4 => ['11:40:00', '12:40:00'],
        5 => ['13:30:00', '14:30:00'],
        6 => ['14:30:00', '15:30:00'],
        7 => ['15:30:00', '16:30:00'],
    ];

    $targetDeptId = (int)($currentStaff['department_id'] ?? 1);
    $targetSemester = (int)($currentStaff['semester'] ?? 6);
    $targetSection = 'A';
    $acadYear = '2024-2025';

    // Clear existing slots for current faculty
    $db->prepare("DELETE FROM timetable WHERE staff_id = ?")->execute([$currentStaffId]);

    // Preload subjects
    $allSubs = $db->query("SELECT id, code FROM subjects")->fetchAll(PDO::FETCH_KEY_PAIR);
    $subCodeToId = [];
    foreach ($allSubs as $id => $code) {
        $subCodeToId[strtoupper(trim($code))] = (int)$id;
    }

    // Preload rooms
    $allRooms = $db->query("SELECT id, room_number FROM classrooms")->fetchAll(PDO::FETCH_KEY_PAIR);
    $roomNoToId = [];
    foreach ($allRooms as $id => $rn) {
        $roomNoToId[strtoupper(trim($rn))] = (int)$id;
    }

    $insSlot = $db->prepare("
        INSERT INTO timetable 
        (department_id, semester, section, day_of_week, period_number, start_time, end_time, subject_id, staff_id, classroom_id, academic_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($modelGrid as $day => $pSlots) {
        foreach ($pSlots as $pNum => $item) {
            $cKey = strtoupper($item['code']);
            if (!isset($subCodeToId[$cKey])) {
                $insSub = $db->prepare("INSERT INTO subjects (code, name, department_id, semester, credits, staff_id) VALUES (?, ?, ?, ?, 3, ?)");
                $insSub->execute([$item['code'], $item['name'], $targetDeptId, $targetSemester, $currentStaffId]);
                $newSubId = (int)$db->lastInsertId();
                $subCodeToId[$cKey] = $newSubId;
            }
            $sId = $subCodeToId[$cKey];

            $rId = null;
            if (!empty($item['room'])) {
                $rKey = strtoupper($item['room']);
                if (!isset($roomNoToId[$rKey])) {
                    $insRm = $db->prepare("INSERT INTO classrooms (room_number, capacity, type) VALUES (?, 60, 'lecture')");
                    $insRm->execute([$item['room']]);
                    $newRmId = (int)$db->lastInsertId();
                    $roomNoToId[$rKey] = $newRmId;
                }
                $rId = $roomNoToId[$rKey];
            }

            $stTime = $periodTimes[$pNum][0];
            $enTime = $periodTimes[$pNum][1];
            $insSlot->execute([$targetDeptId, $targetSemester, $targetSection, $day, $pNum, $stTime, $enTime, $sId, $currentStaffId, $rId, $acadYear]);
            $count++;
        }
    }

    setFlashMessage('success', "Image model timetable schedule loaded successfully! {$count} class slots matched with room numbers and multi-period blocks.");
    redirect("staff/timetable.php?staff_id={$currentStaffId}");
}

// Handle Download Sample Flat CSV Template
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $filename = 'timetable_flat_template.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

    fputcsv($out, [
        'Day',
        'Period',
        'Subject Code',
        'Faculty Code',
        'Room Number',
        'Semester',
        'Section',
        'Department Code',
        'Academic Year'
    ]);

    // Sample data rows
    $sampleStaffCode = $currentStaff['staff_code'] ?? 'STF001';
    $sampleDeptCode = $currentStaff['dept_code'] ?? 'CSE';

    fputcsv($out, ['Monday', '1', 'CS601', $sampleStaffCode, '2214', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Monday', '2', 'CS602', $sampleStaffCode, '2214', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Tuesday', '1', 'CS603', $sampleStaffCode, '2215', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Tuesday', '2', 'CS603', $sampleStaffCode, '2215', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Wednesday', '3', 'CS601', $sampleStaffCode, '2216', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Thursday', '4', 'CS602', $sampleStaffCode, '2214', '6', 'A', $sampleDeptCode, '2024-2025']);
    fputcsv($out, ['Friday', '5', 'CS601', $sampleStaffCode, '2214', '6', 'A', $sampleDeptCode, '2024-2025']);

    fclose($out);
    exit;
}

// Helper to parse spreadsheet rows from CSV, XLSX, or XLS
function parseSpreadsheetRows($filePath, $extension) {
    $rows = [];
    $extension = strtolower($extension);

    // Support HTML-based .xls files (common for Excel HTML exports)
    if ($extension === 'xls') {
        $content = @file_get_contents($filePath);
        if ($content && stripos($content, '<table') !== false) {
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
            $tables = $dom->getElementsByTagName('table');
            foreach ($tables as $tbl) {
                $trs = $tbl->getElementsByTagName('tr');
                if ($trs->length >= 2) {
                    foreach ($trs as $tr) {
                        $cells = [];
                        $childNodes = $tr->childNodes;
                        foreach ($childNodes as $node) {
                            if (in_array(strtolower($node->nodeName), ['td', 'th'])) {
                                $nodeHtml = $node->ownerDocument->saveHTML($node);
                                $text = preg_replace('/<br\s*\/?>/i', "\n", $nodeHtml);
                                $text = trim(strip_tags($text));
                                $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                
                                $colspan = 1;
                                if ($node instanceof DOMElement && $node->hasAttribute('colspan')) {
                                    $colspan = max(1, (int)$node->getAttribute('colspan'));
                                }
                                for ($k = 0; $k < $colspan; $k++) {
                                    $cells[] = $decoded;
                                }
                            }
                        }
                        if (!empty(array_filter($cells, function($v) { return $v !== ''; }))) {
                            $rows[] = $cells;
                        }
                    }
                    if (count($rows) >= 2) {
                        return $rows;
                    }
                }
            }
        }
        // Fallback to CSV parsing if not HTML
        $extension = 'csv';
    }

    if ($extension === 'csv' || $extension === 'txt') {
        $handle = fopen($filePath, 'r');
        if ($handle === false) return false;

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $commaCount = substr_count($firstLine, ',');
        $semiCount  = substr_count($firstLine, ';');
        $tabCount   = substr_count($firstLine, "\t");
        $delimiter  = ',';
        if ($semiCount > $commaCount && $semiCount > $tabCount) {
            $delimiter = ';';
        } elseif ($tabCount > $commaCount && $tabCount > $semiCount) {
            $delimiter = "\t";
        }

        $firstRow = str_getcsv($firstLine, $delimiter);
        if ($firstRow !== false) {
            $rows[] = $firstRow;
        }

        while (($r = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $r;
        }
        fclose($handle);
        return $rows;
    }

    if ($extension === 'xlsx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('/^xl\/worksheets\/sheet\d+\.xml$/i', $stat['name'])) {
                    $sheetXml = $zip->getFromName($stat['name']);
                    break;
                }
            }
        }

        if ($sheetXml === false) {
            $zip->close();
            return false;
        }

        $xml = @simplexml_load_string($sheetXml);
        $zip->close();
        if (!$xml || !isset($xml->sheetData)) {
            return false;
        }

        // Helper to convert Excel cell ref (e.g. "B2") to [rowIdx, colIdx]
        $cellRefToCoords = function($ref) {
            if (!preg_match('/^([A-Z]+)(\d+)$/i', $ref, $m)) return null;
            $colStr = strtoupper($m[1]);
            $rowNum = (int)$m[2] - 1; // 0-indexed
            $colNum = 0;
            $len = strlen($colStr);
            for ($k = 0; $k < $len; $k++) {
                $colNum = $colNum * 26 + (ord($colStr[$k]) - ord('A') + 1);
            }
            return [$rowNum, $colNum - 1];
        };

        // Track merged cell ranges
        $mergeMap = [];
        $gridData = [];
        $maxRowIdx = 0;
        $maxColIdx = 0;

        if (isset($xml->mergeCells->mergeCell)) {
            foreach ($xml->mergeCells->mergeCell as $mc) {
                $ref = (string)$mc['ref']; // e.g. "G2:I2"
                $parts = explode(':', $ref);
                if (count($parts) === 2) {
                    $start = $cellRefToCoords($parts[0]);
                    $end = $cellRefToCoords($parts[1]);
                    if ($start && $end) {
                        if ($end[0] > $maxRowIdx) $maxRowIdx = $end[0];
                        if ($end[1] > $maxColIdx) $maxColIdx = $end[1];
                        for ($r = $start[0]; $r <= $end[0]; $r++) {
                            for ($c = $start[1]; $c <= $end[1]; $c++) {
                                if ($r !== $start[0] || $c !== $start[1]) {
                                    $mergeMap["{$r}_{$c}"] = "{$start[0]}_{$start[1]}";
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($xml->sheetData->row as $row) {
            $rowIdx = ((int)$row['r']) - 1;
            if ($rowIdx < 0) continue;
            if ($rowIdx > $maxRowIdx) $maxRowIdx = $rowIdx;

            foreach ($row->c as $c) {
                $coords = $cellRefToCoords((string)$c['r']);
                if (!$coords) continue;
                $cColIdx = $coords[1];
                if ($cColIdx > $maxColIdx) $maxColIdx = $cColIdx;

                $type = (string)$c['t'];
                $val = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string)$c->is->t;
                } else {
                    $val = (string)$c->v;
                }

                $gridData[$rowIdx][$cColIdx] = $val;
            }
        }

        // Fill merged cells
        foreach ($mergeMap as $target => $source) {
            list($tR, $tC) = explode('_', $target);
            list($sR, $sC) = explode('_', $source);
            if (isset($gridData[(int)$sR][(int)$sC]) && empty($gridData[(int)$tR][(int)$tC])) {
                $gridData[(int)$tR][(int)$tC] = $gridData[(int)$sR][(int)$sC];
            }
        }

        for ($r = 0; $r <= $maxRowIdx; $r++) {
            $rowArr = [];
            for ($c = 0; $c <= $maxColIdx; $c++) {
                $rowArr[] = $gridData[$r][$c] ?? '';
            }
            if (!empty(array_filter($rowArr, function($v) { return $v !== ''; }))) {
                $rows[] = $rowArr;
            }
        }

        return $rows;
    }

    return false;
}

// Handle Export Timetable to Native Excel (.xlsx) — Image Model Format
if (isset($_GET['action']) && in_array($_GET['action'], ['export_excel', 'export_xlsx', 'export_grid'])) {
    $exportStmt = $db->prepare("
        SELECT tt.subject_id, tt.classroom_id, tt.day_of_week, tt.period_number, sub.code AS subject_code, sub.name AS subject_name,
               st.staff_code, CONCAT(st.first_name, ' ', st.last_name) AS faculty_name,
               c.room_number, tt.semester, tt.section, d.code AS dept_code, d.name AS dept_name, tt.academic_year
        FROM timetable tt
        JOIN subjects sub ON tt.subject_id = sub.id
        JOIN staff st ON tt.staff_id = st.id
        JOIN departments d ON tt.department_id = d.id
        LEFT JOIN classrooms c ON tt.classroom_id = c.id
        WHERE tt.staff_id = ?
        ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.period_number ASC
    ");
    $exportStmt->execute([$currentStaffId]);
    $slots = $exportStmt->fetchAll();

    $grid = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    foreach ($slots as $slot) {
        $grid[$slot['day_of_week']][$slot['period_number']] = $slot;
        if ($slot['day_of_week'] === 'Saturday' && !in_array('Saturday', $days)) {
            $days[] = 'Saturday';
        }
    }

    $facultyCode = $currentStaff['staff_code'] ?? 'STF';
    $filename = 'Timetable_Schedule_' . $facultyCode . '_' . date('Ymd_His') . '.xlsx';
    outputTimetableXlsx($grid, $days, $filename);
}

// Handle Export Timetable Grid to CSV (Period matrix model)
if (isset($_GET['action']) && $_GET['action'] === 'export_grid_csv') {
    $exportStmt = $db->prepare("
        SELECT tt.day_of_week, tt.period_number, sub.code AS subject_code, sub.name AS subject_name,
               st.staff_code, CONCAT(st.first_name, ' ', st.last_name) AS faculty_name,
               c.room_number, tt.semester, tt.section, d.code AS dept_code, tt.academic_year
        FROM timetable tt
        JOIN subjects sub ON tt.subject_id = sub.id
        JOIN staff st ON tt.staff_id = st.id
        JOIN departments d ON tt.department_id = d.id
        LEFT JOIN classrooms c ON tt.classroom_id = c.id
        WHERE tt.staff_id = ?
        ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.period_number ASC
    ");
    $exportStmt->execute([$currentStaffId]);
    $slots = $exportStmt->fetchAll();

    $grid = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    foreach ($slots as $slot) {
        $grid[$slot['day_of_week']][$slot['period_number']] = $slot;
        if ($slot['day_of_week'] === 'Saturday' && !in_array('Saturday', $days)) {
            $days[] = 'Saturday';
        }
    }

    $filename = 'timetable_grid_' . ($currentStaff['staff_code'] ?? 'faculty') . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, [
        'Days',
        '08.30 to 09.30',
        '09.30 to 10.30',
        '10.40 to 11.40',
        '11.40 to 12.40',
        '12.40 to 01.30',
        '01.30 to 02.30',
        '02.30 to 03.30',
        '03.30 to 04.30'
    ]);

    $lunchLetters = [
        'Monday' => 'L',
        'Tuesday' => 'U',
        'Wednesday' => 'N',
        'Thursday' => 'C',
        'Friday' => 'H',
        'Saturday' => '-'
    ];

    foreach ($days as $day) {
        $rowCells = [$day];
        for ($p = 1; $p <= 7; $p++) {
            if ($p == 5) {
                $rowCells[] = $lunchLetters[$day] ?? 'L';
            }
            if (isset($grid[$day][$p])) {
                $s = $grid[$day][$p];
                $roomPart = !empty($s['room_number']) ? " ({$s['room_number']})" : "";
                $rowCells[] = "{$s['subject_code']}{$roomPart}";
            } else {
                $rowCells[] = '';
            }
        }
        fputcsv($out, $rowCells);
    }
    fclose($out);
    exit;
}

// Handle Export Timetable to Raw CSV (Data list model for re-import)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $exportStmt = $db->prepare("
        SELECT tt.day_of_week, tt.period_number, sub.code AS subject_code, sub.name AS subject_name,
               st.staff_code, CONCAT(st.first_name, ' ', st.last_name) AS faculty_name,
               c.room_number, tt.semester, tt.section, d.code AS dept_code, tt.academic_year
        FROM timetable tt
        JOIN subjects sub ON tt.subject_id = sub.id
        JOIN staff st ON tt.staff_id = st.id
        JOIN departments d ON tt.department_id = d.id
        LEFT JOIN classrooms c ON tt.classroom_id = c.id
        WHERE tt.staff_id = ?
        ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.period_number ASC
    ");
    $exportStmt->execute([$currentStaffId]);
    $slots = $exportStmt->fetchAll();

    $filename = 'timetable_raw_data_' . ($currentStaff['staff_code'] ?? 'faculty') . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, [
        'Day',
        'Period',
        'Subject Code',
        'Faculty Code',
        'Room Number',
        'Semester',
        'Section',
        'Department Code',
        'Academic Year'
    ]);

    foreach ($slots as $slot) {
        fputcsv($out, [
            $slot['day_of_week'],
            $slot['period_number'],
            $slot['subject_code'],
            $slot['staff_code'],
            $slot['room_number'] ?? '',
            $slot['semester'],
            $slot['section'],
            $slot['dept_code'],
            $slot['academic_year'] ?? '2024-2025'
        ]);
    }
    fclose($out);
    exit;
}

// Handle Import Timetable (Admin Only)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_timetable') {
    if (!$isAdmin) {
        setFlashMessage('danger', 'Unauthorized. Only administrators can import timetables.');
        redirect('staff/timetable.php');
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Invalid security token. Please try again.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        setFlashMessage('danger', 'Please upload a valid Excel or CSV file.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
        setFlashMessage('danger', 'Invalid file format. Please upload an Excel (.xlsx, .xls) or CSV (.csv) file.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    $rawRows = parseSpreadsheetRows($fileTmpPath, $fileExtension);
    if ($rawRows === false || empty($rawRows)) {
        setFlashMessage('danger', 'Failed to read spreadsheet file or the uploaded file is empty.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    // Preload subjects lookup
    $allSubjects = $db->query("SELECT id, code, name, department_id, semester, staff_id FROM subjects")->fetchAll();
    $subMap = [];
    foreach ($allSubjects as $sub) {
        $subMap[strtoupper(trim($sub['code']))] = $sub;
        $subMap[strtoupper(trim($sub['name']))] = $sub;
    }

    // Preload staff lookup
    $staffList = $db->query("SELECT id, staff_code, first_name, last_name, department_id FROM staff")->fetchAll();
    $stfMap = [];
    foreach ($staffList as $st) {
        $stfMap[strtoupper(trim($st['staff_code']))] = (int)$st['id'];
        $stfMap[strtoupper(trim($st['first_name'] . ' ' . $st['last_name']))] = (int)$st['id'];
        $stfMap[strtoupper(trim($st['first_name']))] = (int)$st['id'];
        $stfMap[(string)$st['id']] = (int)$st['id'];
    }

    // Preload classrooms lookup
    $roomsList = $db->query("SELECT id, room_number FROM classrooms")->fetchAll();
    $roomMap = [];
    foreach ($roomsList as $rm) {
        $roomMap[strtoupper(trim($rm['room_number']))] = (int)$rm['id'];
    }

    // Preload departments lookup
    $deptsList = $db->query("SELECT id, code, name FROM departments")->fetchAll();
    $deptMap = [];
    foreach ($deptsList as $d) {
        $deptMap[strtoupper(trim($d['code']))] = (int)$d['id'];
        $deptMap[strtoupper(trim($d['name']))] = (int)$d['id'];
        $deptMap[(string)$d['id']] = (int)$d['id'];
    }

    // Period timing definitions (aligned to the image model)
    $periodTimes = [
        1 => ['08:30:00', '09:30:00'],
        2 => ['09:30:00', '10:30:00'],
        3 => ['10:40:00', '11:40:00'],
        4 => ['11:40:00', '12:40:00'],
        5 => ['13:30:00', '14:30:00'],
        6 => ['14:30:00', '15:30:00'],
        7 => ['15:30:00', '16:30:00'],
    ];

    // Read header row
    $headerRow = array_shift($rawRows);
    if ($headerRow === null || empty($headerRow)) {
        setFlashMessage('danger', 'Uploaded spreadsheet does not contain any header columns.');
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $dayAliases = [
        'mon' => 'Monday', 'monday' => 'Monday',
        'tue' => 'Tuesday', 'tues' => 'Tuesday', 'tuesday' => 'Tuesday',
        'wed' => 'Wednesday', 'wednesday' => 'Wednesday',
        'thu' => 'Thursday', 'thur' => 'Thursday', 'thurs' => 'Thursday', 'thursday' => 'Thursday',
        'fri' => 'Friday', 'friday' => 'Friday',
        'sat' => 'Saturday', 'saturday' => 'Saturday',
    ];

    $checkExistingSlot = $db->prepare("
        SELECT id FROM timetable 
        WHERE department_id = ? AND semester = ? AND section = ? AND day_of_week = ? AND period_number = ? 
        LIMIT 1
    ");

    $insertStmt = $db->prepare("
        INSERT INTO timetable 
        (department_id, semester, section, day_of_week, period_number, start_time, end_time, subject_id, staff_id, classroom_id, academic_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $updateStmt = $db->prepare("
        UPDATE timetable 
        SET subject_id = ?, staff_id = ?, classroom_id = ?, start_time = ?, end_time = ?, academic_year = ?
        WHERE id = ?
    ");

    $replaceExisting = isset($_POST['replace_existing']) && $_POST['replace_existing'] == '1';

    // Detect whether file is Grid Format (Image model: Days vs Period times/slots) OR Flat List Format
    $isGridFormat = false;
    $firstHeader = strtolower(trim($headerRow[0] ?? ''));
    if (in_array($firstHeader, ['days', 'day', 'day / period', 'day/period', 'day & period', 'weekday'])) {
        $isGridFormat = true;
    } else {
        $dayMatches = 0;
        foreach ($rawRows as $testRow) {
            $c0 = strtolower(trim($testRow[0] ?? ''));
            if (in_array($c0, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
                $dayMatches++;
            }
        }
        if ($dayMatches >= 2) {
            $isGridFormat = true;
        }
    }

    // ==========================================
    // PATH A: GRID FORMAT (IMAGE MODEL FORMAT)
    // ==========================================
    if ($isGridFormat) {
        $gridPeriodCols = [];
        $lunchCol = -1;
        $assignedP = 1;

        for ($c = 1; $c < count($headerRow); $c++) {
            $hVal = strtolower(trim($headerRow[$c] ?? ''));
            if (empty($hVal)) continue;

            if (strpos($hVal, 'lunch') !== false || strpos($hVal, 'recess') !== false || strpos($hVal, '12.40 to') !== false || strpos($hVal, '12:40 to') !== false || ($c == 5 && (strpos($hVal, '12.40') !== false || strpos($hVal, '12:40') !== false))) {
                $lunchCol = $c;
                continue;
            }

            if (preg_match('/p([1-7])/i', $hVal, $m)) {
                $gridPeriodCols[$c] = (int)$m[1];
            } elseif (preg_match('/period\s*([1-7])/i', $hVal, $m)) {
                $gridPeriodCols[$c] = (int)$m[1];
            } elseif (strpos($hVal, '08.30') !== false || strpos($hVal, '08:30') !== false || strpos($hVal, '09:00') !== false) {
                $gridPeriodCols[$c] = 1;
            } elseif (strpos($hVal, '09.30') !== false || strpos($hVal, '09:30') !== false || strpos($hVal, '09:50') !== false) {
                $gridPeriodCols[$c] = 2;
            } elseif (strpos($hVal, '10.40') !== false || strpos($hVal, '10:40') !== false || strpos($hVal, '10:50') !== false) {
                $gridPeriodCols[$c] = 3;
            } elseif (strpos($hVal, '11.40') !== false || strpos($hVal, '11:40') !== false) {
                $gridPeriodCols[$c] = 4;
            } elseif (strpos($hVal, '01.30') !== false || strpos($hVal, '13:30') !== false) {
                $gridPeriodCols[$c] = 5;
            } elseif (strpos($hVal, '02.30') !== false || strpos($hVal, '14:20') !== false || strpos($hVal, '14:30') !== false) {
                $gridPeriodCols[$c] = 6;
            } elseif (strpos($hVal, '03.30') !== false || strpos($hVal, '15:15') !== false || strpos($hVal, '15:30') !== false) {
                $gridPeriodCols[$c] = 7;
            } else {
                if ($assignedP <= 7) {
                    $gridPeriodCols[$c] = $assignedP++;
                }
            }
        }

        if ($replaceExisting && $currentStaffId > 0) {
            $db->prepare("DELETE FROM timetable WHERE staff_id = ?")->execute([$currentStaffId]);
        }

        $targetDeptId = (int)($currentStaff['department_id'] ?? 1);
        $targetSemester = (int)($currentStaff['semester'] ?? 6);
        $targetSection = 'A';
        $acadYear = '2024-2025';

        $importedCount = 0;

        foreach ($rawRows as $row) {
            if (empty(array_filter($row, function($v) { return trim($v) !== ''; }))) {
                continue;
            }

            $rawDay = strtolower(trim($row[0] ?? ''));
            $dayOfWeek = $dayAliases[$rawDay] ?? ucfirst($rawDay);
            if (!in_array($dayOfWeek, $validDays)) {
                continue;
            }

            foreach ($gridPeriodCols as $colIdx => $periodNum) {
                if ($periodNum < 1 || $periodNum > 7) continue;

                $cellVal = trim($row[$colIdx] ?? '');
                if ($cellVal === '' || $cellVal === '-' || strtolower($cellVal) === '- free -' || strtolower($cellVal) === 'free' || strtolower($cellVal) === 'recess') {
                    continue;
                }

                // Extract room number: e.g. "(2316)" or "Room 2316"
                $roomNumber = null;
                $cleanSubText = $cellVal;
                if (preg_match('/[\(\[]\s*([a-zA-Z0-9\-_]+)\s*[\)\]]/', $cellVal, $m)) {
                    $roomNumber = trim($m[1]);
                    $cleanSubText = trim(str_replace($m[0], '', $cellVal));
                } elseif (preg_match('/(?:room|lab)\s*[:\-#]?\s*([a-zA-Z0-9\-_]+)/i', $cellVal, $m)) {
                    $roomNumber = trim($m[1]);
                    $cleanSubText = trim(preg_replace('/(?:room|lab)\s*[:\-#]?\s*[a-zA-Z0-9\-_]+/i', '', $cellVal));
                }

                // Clean subject candidate
                $subLines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $cleanSubText))));
                $subjectCandidate = $subLines[0] ?? $cleanSubText;
                $subjectCandidate = trim(preg_replace('/^[•\-\*]\s*/', '', $subjectCandidate));
                if (empty($subjectCandidate)) continue;

                // Resolve or auto-create classroom
                $classroomId = null;
                if (!empty($roomNumber)) {
                    $roomKey = strtoupper($roomNumber);
                    if (isset($roomMap[$roomKey])) {
                        $classroomId = $roomMap[$roomKey];
                    } else {
                        $insRoom = $db->prepare("INSERT INTO classrooms (room_number, capacity, type) VALUES (?, 60, 'lecture')");
                        $insRoom->execute([$roomNumber]);
                        $classroomId = (int)$db->lastInsertId();
                        $roomMap[$roomKey] = $classroomId;
                    }
                }

                // Resolve or auto-create subject
                $subKey = strtoupper($subjectCandidate);
                $subjectId = null;
                if (isset($subMap[$subKey])) {
                    $subjectId = (int)$subMap[$subKey]['id'];
                } else {
                    foreach ($allSubjects as $sub) {
                        if (strtoupper($sub['code']) === $subKey) {
                            $subjectId = (int)$sub['id'];
                            break;
                        }
                    }
                }

                if (!$subjectId) {
                    $subName = "Course {$subjectCandidate}";
                    if ($subKey === 'P&T') $subName = "Placement & Training";
                    elseif ($subKey === 'G') $subName = "General / Lab Practical";

                    $insSub = $db->prepare("
                        INSERT INTO subjects (code, name, department_id, semester, credits, staff_id)
                        VALUES (?, ?, ?, ?, 3, ?)
                    ");
                    $insSub->execute([$subjectCandidate, $subName, $targetDeptId, $targetSemester, $currentStaffId]);
                    $subjectId = (int)$db->lastInsertId();
                    $newSub = ['id' => $subjectId, 'code' => $subjectCandidate, 'name' => $subName, 'department_id' => $targetDeptId, 'semester' => $targetSemester, 'staff_id' => $currentStaffId];
                    $subMap[$subKey] = $newSub;
                    $allSubjects[] = $newSub;
                }

                $startTime = $periodTimes[$periodNum][0];
                $endTime = $periodTimes[$periodNum][1];

                // Upsert slot
                $checkSlot = $db->prepare("
                    SELECT id FROM timetable 
                    WHERE staff_id = ? AND day_of_week = ? AND period_number = ?
                    LIMIT 1
                ");
                $checkSlot->execute([$currentStaffId, $dayOfWeek, $periodNum]);
                $existingId = $checkSlot->fetchColumn();

                if ($existingId) {
                    $updateStmt->execute([$subjectId, $currentStaffId, $classroomId, $startTime, $endTime, $acadYear, $existingId]);
                } else {
                    $insertStmt->execute([$targetDeptId, $targetSemester, $targetSection, $dayOfWeek, $periodNum, $startTime, $endTime, $subjectId, $currentStaffId, $classroomId, $acadYear]);
                }
                $importedCount++;
            }
        }

        setFlashMessage('success', "Timetable imported successfully from Excel model! {$importedCount} class slots updated. Main timetable updated automatically.");
        redirect("staff/timetable.php?staff_id={$currentStaffId}");
    }

    // ==========================================
    // PATH B: FLAT LIST FORMAT (CSV LIST FORMAT)
    // ==========================================
    $colMap = [];
    foreach ($headerRow as $idx => $headerName) {
        $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim($headerName));
        $cleaned = strtolower(str_replace([' ', '_', '-', '/', '.'], '', $cleaned));

        if (in_array($cleaned, ['day', 'dayofweek', 'weekday'])) {
            $colMap['day'] = $idx;
        } elseif (in_array($cleaned, ['period', 'periodnumber', 'slot', 'periodno', 'hour'])) {
            $colMap['period'] = $idx;
        } elseif (in_array($cleaned, ['subjectcode', 'subject', 'subcode', 'coursecode', 'course'])) {
            $colMap['subject'] = $idx;
        } elseif (in_array($cleaned, ['facultycode', 'staffcode', 'faculty', 'staff', 'teacher', 'professor', 'instructor'])) {
            $colMap['staff'] = $idx;
        } elseif (in_array($cleaned, ['roomnumber', 'room', 'classroom', 'lab', 'roomno'])) {
            $colMap['room'] = $idx;
        } elseif (in_array($cleaned, ['semester', 'sem'])) {
            $colMap['semester'] = $idx;
        } elseif (in_array($cleaned, ['section', 'sec'])) {
            $colMap['section'] = $idx;
        } elseif (in_array($cleaned, ['departmentcode', 'deptcode', 'department', 'dept'])) {
            $colMap['department'] = $idx;
        } elseif (in_array($cleaned, ['academicyear', 'acadyear', 'year'])) {
            $colMap['academic_year'] = $idx;
        }
    }

    // Fallbacks
    if (!isset($colMap['day'])) $colMap['day'] = 0;
    if (!isset($colMap['period'])) $colMap['period'] = 1;
    if (!isset($colMap['subject'])) $colMap['subject'] = 2;
    if (!isset($colMap['staff'])) $colMap['staff'] = 3;
    if (!isset($colMap['room'])) $colMap['room'] = 4;
    if (!isset($colMap['semester'])) $colMap['semester'] = 5;
    if (!isset($colMap['section'])) $colMap['section'] = 6;
    if (!isset($colMap['department'])) $colMap['department'] = 7;
    if (!isset($colMap['academic_year'])) $colMap['academic_year'] = 8;

    // Optional: Clear previous slots if user checked "Replace"
    $replaceExisting = isset($_POST['replace_existing']) && $_POST['replace_existing'] == '1';
    if ($replaceExisting && $currentStaffId > 0) {
        $db->prepare("DELETE FROM timetable WHERE staff_id = ?")->execute([$currentStaffId]);
    }

    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $dayAliases = [
        'mon' => 'Monday', 'monday' => 'Monday',
        'tue' => 'Tuesday', 'tues' => 'Tuesday', 'tuesday' => 'Tuesday',
        'wed' => 'Wednesday', 'wednesday' => 'Wednesday',
        'thu' => 'Thursday', 'thur' => 'Thursday', 'thurs' => 'Thursday', 'thursday' => 'Thursday',
        'fri' => 'Friday', 'friday' => 'Friday',
        'sat' => 'Saturday', 'saturday' => 'Saturday',
    ];

    $checkExistingSlot = $db->prepare("
        SELECT id FROM timetable 
        WHERE department_id = ? AND semester = ? AND section = ? AND day_of_week = ? AND period_number = ? 
        LIMIT 1
    ");

    $insertStmt = $db->prepare("
        INSERT INTO timetable 
        (department_id, semester, section, day_of_week, period_number, start_time, end_time, subject_id, staff_id, classroom_id, academic_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $updateStmt = $db->prepare("
        UPDATE timetable 
        SET subject_id = ?, staff_id = ?, classroom_id = ?, start_time = ?, end_time = ?, academic_year = ?
        WHERE id = ?
    ");

    $importedCount = 0;
    $skippedCount = 0;
    $skippedReasons = [];
    $rowNumber = 1;
    $lastAssignedStaffId = $currentStaffId;

    foreach ($rawRows as $row) {
        $rowNumber++;
        if (empty(array_filter($row))) {
            continue;
        }

        $rawDay = strtolower(trim($row[$colMap['day']] ?? ''));
        $dayOfWeek = $dayAliases[$rawDay] ?? ucfirst($rawDay);
        if (!in_array($dayOfWeek, $validDays)) {
            $skippedCount++;
            if (count($skippedReasons) < 5) $skippedReasons[] = "Row {$rowNumber}: Invalid day '{$rawDay}'";
            continue;
        }

        $periodNum = (int)($row[$colMap['period']] ?? 0);
        if ($periodNum < 1 || $periodNum > 7) {
            $skippedCount++;
            if (count($skippedReasons) < 5) $skippedReasons[] = "Row {$rowNumber}: Period must be between 1 and 7";
            continue;
        }

        $rawSub = strtoupper(trim($row[$colMap['subject']] ?? ''));
        if (empty($rawSub) || !isset($subMap[$rawSub])) {
            $skippedCount++;
            if (count($skippedReasons) < 5) $skippedReasons[] = "Row {$rowNumber}: Subject code '{$rawSub}' not found";
            continue;
        }
        $subObj = $subMap[$rawSub];
        $subjectId = (int)$subObj['id'];

        // Resolve staff
        $rawStaff = strtoupper(trim($row[$colMap['staff']] ?? ''));
        if (!empty($rawStaff) && isset($stfMap[$rawStaff])) {
            $assignedStaffId = $stfMap[$rawStaff];
        } else {
            $assignedStaffId = $currentStaffId ?: ($subObj['staff_id'] ?: 1);
        }
        if ($assignedStaffId > 0) {
            $lastAssignedStaffId = $assignedStaffId;
        }

        // Resolve classroom
        $rawRoom = strtoupper(trim($row[$colMap['room']] ?? ''));
        $classroomId = isset($roomMap[$rawRoom]) ? $roomMap[$rawRoom] : null;

        // Resolve department
        $rawDept = strtoupper(trim($row[$colMap['department']] ?? ''));
        if (!empty($rawDept) && isset($deptMap[$rawDept])) {
            $deptId = $deptMap[$rawDept];
        } else {
            $deptId = (int)$subObj['department_id'];
        }

        // Semester & Section
        $semVal = (int)($row[$colMap['semester']] ?? 0);
        if ($semVal <= 0) $semVal = (int)$subObj['semester'];
        $secVal = strtoupper(trim($row[$colMap['section']] ?? 'A'));
        if (empty($secVal)) $secVal = 'A';

        // Academic Year
        $acadYear = trim($row[$colMap['academic_year']] ?? '');
        if (empty($acadYear)) $acadYear = '2024-2025';

        // Start and End Times
        $startTime = $periodTimes[$periodNum][0];
        $endTime = $periodTimes[$periodNum][1];

        // Upsert slot
        try {
            $checkExistingSlot->execute([$deptId, $semVal, $secVal, $dayOfWeek, $periodNum]);
            $existingId = $checkExistingSlot->fetchColumn();

            if ($existingId) {
                $updateStmt->execute([$subjectId, $assignedStaffId, $classroomId, $startTime, $endTime, $acadYear, $existingId]);
            } else {
                $insertStmt->execute([$deptId, $semVal, $secVal, $dayOfWeek, $periodNum, $startTime, $endTime, $subjectId, $assignedStaffId, $classroomId, $acadYear]);
            }
            $importedCount++;
        } catch (Exception $e) {
            $skippedCount++;
            if (count($skippedReasons) < 5) $skippedReasons[] = "Row {$rowNumber}: " . $e->getMessage();
        }
    }

    $targetRedirectStaffId = $currentStaffId ?: $lastAssignedStaffId;

    if ($importedCount > 0) {
        $msg = "Timetable imported successfully! {$importedCount} class slots updated. Schedule refreshed automatically.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} rows skipped: " . implode('; ', $skippedReasons) . ")";
        }
        setFlashMessage('success', $msg);
    } else {
        $msg = "No timetable slots were imported.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} rows skipped: " . implode('; ', $skippedReasons) . ")";
        }
        setFlashMessage('warning', $msg);
    }

    redirect("staff/timetable.php?staff_id={$targetRedirectStaffId}");
}

// Fetch Timetable Entries for selected staff
$ttStmt = $db->prepare("
    SELECT tt.*, sub.code AS subject_code, sub.name AS subject_name, c.room_number, d.code AS dept_code
    FROM timetable tt
    JOIN subjects sub ON tt.subject_id = sub.id
    JOIN departments d ON tt.department_id = d.id
    LEFT JOIN classrooms c ON tt.classroom_id = c.id
    WHERE tt.staff_id = ?
    ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.period_number ASC
");
$ttStmt->execute([$currentStaffId]);
$timetable = $ttStmt->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$grid = [];
foreach ($timetable as $t) {
    $grid[$t['day_of_week']][$t['period_number']] = $t;
    if ($t['day_of_week'] === 'Saturday' && !in_array('Saturday', $days)) {
        $days[] = 'Saturday';
    }
}

$pageTitle = "Faculty Teaching Timetable";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
                   <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Faculty Teaching Schedule</h1>
                    <p class="text-muted small mb-0">
                        Weekly lecture schedule across assigned subjects and sections
                        <?php if ($currentStaff): ?>
                            • <span class="text-dark font-weight-600">Prof. <?= htmlspecialchars($currentStaff['first_name'] . ' ' . $currentStaff['last_name']) ?> (<?= htmlspecialchars($currentStaff['staff_code']) ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if ($isAdmin): ?>
                        <!-- Faculty Selector (Admin Only) -->
                        <form method="GET" action="" class="d-inline-flex align-items-center gap-1 me-1">
                            <label for="staff_select" class="small text-muted font-weight-500 mb-0 d-none d-sm-inline">Faculty:</label>
                            <select id="staff_select" name="staff_id" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()" style="min-width: 230px;">
                                <?php foreach ($allStaff as $st): ?>
                                    <option value="<?= $st['id'] ?>" <?= $st['id'] == $currentStaffId ? 'selected' : '' ?>>
                                        Prof. <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?> (<?= htmlspecialchars($st['staff_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <!-- Import Excel / CSV Button (Admin Only) -->
                        <button type="button" class="btn btn-outline-primary rounded-pill btn-sm px-3 font-weight-600" data-bs-toggle="modal" data-bs-target="#importTimetableModal" title="Import Timetable Excel/CSV (Admin only)">
                            <i class="fas fa-file-import me-1"></i> Import Excel / CSV
                        </button>
                    <?php endif; ?>

                    <!-- Load Exact Model Schedule Button -->
                    <form method="POST" action="?staff_id=<?= $currentStaffId ?>" class="d-inline" onsubmit="return confirm('Load the exact image model schedule (Subjects A, B, C, D, E, G, P&T with rooms 2316, 2215, 2213, 2211, 2317, 2214, 2318) for this faculty?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="load_model_schedule">
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm px-3 font-weight-600 shadow-sm" title="Apply the exact schedule from the model image">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Load Model Schedule
                        </button>
                    </form>

                    <!-- Print / PDF Button -->
                    <button type="button" onclick="window.print()" class="btn btn-outline-dark rounded-pill btn-sm px-3 font-weight-600" title="Print or save as PDF">
                        <i class="fas fa-print me-1"></i> Print / PDF
                    </button>

                    <!-- Export Excel & Options -->
                    <div class="btn-group shadow-sm" role="group">
                        <a href="?action=export_excel&staff_id=<?= $currentStaffId ?>" class="btn btn-success rounded-pill-start btn-sm px-3 font-weight-600" title="Export Excel Timetable (.xlsx)">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split rounded-pill-end px-2" data-bs-toggle="dropdown" aria-expanded="false" title="More Export Formats">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px; font-size: 0.88rem;">
                            <li>
                                <a class="dropdown-item py-2 fw-semibold text-success" href="?action=export_excel&staff_id=<?= $currentStaffId ?>">
                                    <i class="fas fa-file-excel me-2 text-success"></i> Export Excel Schedule (.xlsx)
                                    <div class="text-muted small ps-4">Formatted timetable grid model</div>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="?action=export_grid_csv&staff_id=<?= $currentStaffId ?>">
                                    <i class="fas fa-table me-2 text-primary"></i> Timetable Grid Matrix (.csv)
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-muted" href="?action=export_csv&staff_id=<?= $currentStaffId ?>">
                                    <i class="fas fa-file-csv me-2 text-secondary"></i> Raw Slots List (.csv)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Timetable Box Model Container (Faithful to Attached Image Model) -->
            <div class="card border-0 shadow-sm timetable-box-card mb-4" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark text-white rounded-pill px-3 py-2 font-weight-600" style="font-size: 0.82rem;">
                            <i class="fas fa-table me-1"></i> Master Timetable Box Model
                        </span>
                        <span class="text-muted small font-weight-500">
                            High-contrast period matrix with continuous vertical lunch column &amp; slot spans
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark border px-3 py-2 font-weight-600 rounded-pill">
                            <i class="fas fa-bell text-warning me-1"></i> Recess: 12.40 PM – 01.30 PM
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4 pt-1">
                    <div class="table-responsive timetable-table-wrapper">
                        <table class="table timetable-box-table align-middle text-center mb-0" id="facultyTimetableGrid">
                            <thead>
                                <tr>
                                    <th class="tt-days-head">Days</th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">08.30</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">09.30</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">09.30</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">10.30</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">10.40</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">11.40</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">11.40</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">12.40</span>
                                        </div>
                                    </th>
                                    <th class="tt-lunch-head">
                                        <div class="time-header-box">
                                            <span class="t-top">12.40</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">01.30</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">01.30</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">02.30</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">02.30</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">03.30</span>
                                        </div>
                                    </th>
                                    <th class="tt-period-head">
                                        <div class="time-header-box">
                                            <span class="t-top">03.30</span>
                                            <span class="t-mid">to</span>
                                            <span class="t-bot">04.30</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dayIndex = 0;
                                foreach ($days as $day): 
                                ?>
                                    <tr>
                                        <!-- Days Column -->
                                        <td class="day-cell align-middle text-center"><?= $day ?></td>
                                        
                                        <?php 
                                        $p = 1;
                                        while ($p <= 7): 
                                            // Unified continuous vertical lunch break (spans all days, rendered only on row 0)
                                            if ($p == 5): 
                                                if ($dayIndex === 0): ?>
                                                    <td rowspan="<?= count($days) ?>" class="lunch-cell align-middle text-center" title="Lunch Break (12.40 to 01.30)">
                                                        <div class="lunch-stack">
                                                            <span class="lunch-letter">L</span>
                                                            <span class="lunch-letter">U</span>
                                                            <span class="lunch-letter">N</span>
                                                            <span class="lunch-letter">C</span>
                                                            <span class="lunch-letter">H</span>
                                                        </div>
                                                    </td>
                                                <?php endif; 
                                            endif; 

                                            if (isset($grid[$day][$p])): 
                                                $slot = $grid[$day][$p];
                                                $maxP = ($p <= 4) ? 4 : 7;
                                                $span = 1;
                                                for ($nextP = $p + 1; $nextP <= $maxP; $nextP++) {
                                                    if (isset($grid[$day][$nextP]) && 
                                                        ($grid[$day][$nextP]['subject_code'] ?? '') === ($slot['subject_code'] ?? '') && 
                                                        ($grid[$day][$nextP]['room_number'] ?? '') === ($slot['room_number'] ?? '')) {
                                                        $span++;
                                                    } else {
                                                        break;
                                                    }
                                                }

                                                if ($span > 1): ?>
                                                    <!-- Multi-period Spanned Slot (e.g. G or P&T) -->
                                                    <td colspan="<?= $span ?>" class="slot-cell align-middle text-center <?= $span >= 3 ? 'slot-wide-span' : '' ?>" title="<?= htmlspecialchars($slot['subject_name'] ?? $slot['subject_code']) ?><?= !empty($slot['room_number']) ? ' - Room ' . htmlspecialchars($slot['room_number']) : '' ?>">
                                                        <div class="slot-box">
                                                            <div class="slot-code"><?= htmlspecialchars($slot['subject_code']) ?></div>
                                                            <?php if (!empty($slot['room_number'])): ?>
                                                                <div class="slot-room">(<?= htmlspecialchars($slot['room_number']) ?>)</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <?php 
                                                    $p += $span;
                                                else: ?>
                                                    <!-- Single Period Slot (e.g. A (2316), C (2215)) -->
                                                    <td class="slot-cell align-middle text-center" title="<?= htmlspecialchars($slot['subject_name'] ?? $slot['subject_code']) ?><?= !empty($slot['room_number']) ? ' - Room ' . htmlspecialchars($slot['room_number']) : '' ?>">
                                                        <div class="slot-box">
                                                            <div class="slot-code"><?= htmlspecialchars($slot['subject_code']) ?></div>
                                                            <?php if (!empty($slot['room_number'])): ?>
                                                                <div class="slot-room">(<?= htmlspecialchars($slot['room_number']) ?>)</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <?php 
                                                    $p++;
                                                endif;
                                            else: ?>
                                                <!-- Empty Box Cell -->
                                                <td class="slot-cell slot-empty align-middle text-center"></td>
                                                <?php 
                                                $p++;
                                            endif;
                                        endwhile; 
                                        $dayIndex++;
                                        ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Subject Code Reference Directory Card -->
            <?php 
            $uniqueSubjects = [];
            foreach ($grid as $dSlots) {
                foreach ($dSlots as $sl) {
                    $cKey = $sl['subject_code'];
                    if (!isset($uniqueSubjects[$cKey])) {
                        $uniqueSubjects[$cKey] = [
                            'code' => $sl['subject_code'],
                            'name' => $sl['subject_name'] ?? "Subject {$sl['subject_code']}",
                            'rooms' => []
                        ];
                    }
                    if (!empty($sl['room_number']) && !in_array($sl['room_number'], $uniqueSubjects[$cKey]['rooms'])) {
                        $uniqueSubjects[$cKey]['rooms'][] = $sl['room_number'];
                    }
                }
            }
            ksort($uniqueSubjects);
            ?>
            <div class="card border-0 shadow-sm subject-directory-card" style="border-radius: 16px; background: #ffffff;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-1">
                                <i class="fas fa-book-bookmark text-primary me-2"></i> Subject &amp; Course Mapping Directory
                            </h5>
                            <p class="text-muted small mb-0">Course codes, full course titles, and allocated classroom/laboratory numbers</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 font-weight-600 rounded-pill">
                            <?= count($uniqueSubjects) ?> Scheduled Subjects
                        </span>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($uniqueSubjects as $sub): ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="p-3 rounded-3 border h-100 bg-light-subtle d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-dark text-white font-weight-700 px-3 py-2" style="font-size: 1rem; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($sub['code']) ?>
                                        </span>
                                        <?php if (!empty($sub['rooms'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary font-weight-600">
                                                <i class="fas fa-door-open me-1"></i> Room <?= htmlspecialchars(implode(', ', $sub['rooms'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border font-weight-500">Seminar / Lab</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-weight-600 text-dark" style="font-size: 0.95rem;">
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* ============================================================
   ATTACHED IMAGE MODEL DESIGN & CRISP BOX MODEL TABLE
   ============================================================ */
.timetable-box-card {
    border: 1px solid #d1d5db !important;
}

.timetable-table-wrapper {
    background-color: #ffffff;
    padding: 1px;
    overflow-x: auto;
}

/* Master Box Model Table: Crisp High-Contrast Black/Dark Borders */
.timetable-box-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse !important;
    border: 2px solid #000000 !important;
    background-color: #ffffff !important;
}

.timetable-box-table th,
.timetable-box-table td {
    border: 1.5px solid #000000 !important;
    vertical-align: middle;
    text-align: center;
    background-color: #ffffff !important;
    color: #000000 !important;
    padding: 0;
}

/* Table Header */
.timetable-box-table thead tr {
    height: 78px;
    background-color: #ffffff !important;
}

.timetable-box-table thead th {
    background-color: #ffffff !important;
    color: #000000 !important;
    vertical-align: middle;
    padding: 10px 4px;
}

.timetable-box-table thead th.tt-days-head {
    width: 130px;
    min-width: 120px;
    max-width: 140px;
    font-size: 1.25rem;
    font-weight: 800;
    color: #000000 !important;
    letter-spacing: 0.5px;
}

.timetable-box-table thead th.tt-period-head {
    min-width: 110px;
}

.timetable-box-table thead th.tt-lunch-head {
    width: 76px;
    min-width: 70px;
    max-width: 82px;
}

/* Header Period Time Stacking (08.30 / to / 09.30) */
.time-header-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.2;
}

.time-header-box .t-top,
.time-header-box .t-bot {
    font-size: 1.05rem;
    font-weight: 800;
    color: #000000;
    letter-spacing: 0.2px;
}

.time-header-box .t-mid {
    font-size: 0.95rem;
    font-weight: 700;
    color: #000000;
    margin: 2px 0;
}

/* Body Rows */
.timetable-box-table tbody tr {
    height: 90px;
    background-color: #ffffff !important;
}

/* Days Column */
.timetable-box-table td.day-cell {
    font-size: 1.15rem;
    font-weight: 800;
    color: #000000 !important;
    background-color: #ffffff !important;
    letter-spacing: 0.3px;
    padding: 6px;
}

/* Continuous Vertical Lunch Column */
.timetable-box-table td.lunch-cell {
    width: 76px;
    min-width: 70px;
    max-width: 82px;
    background-color: #ffffff !important;
    padding: 14px 0;
    vertical-align: middle;
}

.lunch-stack {
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
    align-items: center;
    height: 100%;
    min-height: 420px;
    padding: 12px 0;
}

.lunch-stack .lunch-letter {
    font-size: 1.35rem;
    font-weight: 800;
    color: #000000;
    line-height: 1;
    display: block;
}

/* Slot Box Cell */
.timetable-box-table td.slot-cell {
    min-width: 110px;
    background-color: #ffffff !important;
    padding: 8px 6px;
    transition: background-color 0.15s ease-in-out;
}

.timetable-box-table td.slot-cell:hover {
    background-color: #fbfbfb !important;
}

.slot-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.slot-code {
    font-size: 1.35rem;
    font-weight: 800;
    color: #000000;
    line-height: 1.15;
}

.slot-room {
    font-size: 1.1rem;
    font-weight: 700;
    color: #000000;
    margin-top: 3px;
    line-height: 1.1;
}

/* Wide Spanned Multi-Period Slot (like G or P&T spanning 3 periods) */
.timetable-box-table td.slot-wide-span .slot-code {
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: 1px;
}

/* Empty Cell */
.timetable-box-table td.slot-empty {
    background-color: #ffffff !important;
}

/* Subject Directory Card */
.subject-directory-card {
    border: 1px solid #e5e7eb !important;
}

/* High-Quality Print Stylesheet */
@media print {
    #sidebar-wrapper,
    #page-content-wrapper > nav,
    .btn,
    .btn-group,
    form,
    .subject-directory-card,
    .modal,
    .alert {
        display: none !important;
    }
    #wrapper {
        display: block !important;
    }
    #page-content-wrapper {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    .timetable-box-card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
    }
    .timetable-table-wrapper {
        overflow: visible !important;
    }
    .timetable-box-table {
        width: 100% !important;
        border: 2px solid #000000 !important;
        page-break-inside: avoid;
    }
    .timetable-box-table th,
    .timetable-box-table td {
        border: 1.5px solid #000000 !important;
        color: #000000 !important;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
}
</style>
</div>

<?php if ($isAdmin): ?>
<!-- Modal: Import Timetable Excel/CSV (Admin Only) -->
<div class="modal fade" id="importTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-file-excel text-success me-2"></i> Import Timetable Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?staff_id=<?= $currentStaffId ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_timetable">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 p-3 mb-4 bg-info-light">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-info-circle text-info me-1"></i> Timetable Import Guidelines</h6>
                                <p class="small text-muted mb-1">Upload an Excel (<strong>.xlsx, .xls</strong>) or CSV (<strong>.csv</strong>) file. The timetable grid will automatically update and reflect in the main view upon upload.</p>
                                <span class="badge bg-primary-subtle text-primary fw-semibold me-1"><i class="fas fa-check me-1"></i> Model Grid Format Supported</span>
                                <span class="badge bg-secondary-subtle text-secondary fw-semibold"><i class="fas fa-check me-1"></i> Flat List Format Supported</span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <a href="?action=download_model_excel" class="btn btn-sm btn-success rounded-pill font-weight-600 px-3 shadow-sm text-nowrap">
                                    <i class="fas fa-file-excel me-1"></i> Download Excel Model Template (.xlsx)
                                </a>
                                <a href="?action=download_template" class="btn btn-sm btn-outline-secondary rounded-pill font-weight-600 px-3 text-nowrap">
                                    <i class="fas fa-file-csv me-1"></i> Flat CSV Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="import_staff_id" class="form-label font-weight-600 text-dark">Target Faculty Member <span class="text-danger">*</span></label>
                        <select id="import_staff_id" name="staff_id" class="form-select form-select-lg">
                            <?php foreach ($allStaff as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= $st['id'] == $currentStaffId ? 'selected' : '' ?>>
                                    Prof. <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?> (<?= htmlspecialchars($st['staff_code']) ?>) — <?= htmlspecialchars($st['dept_code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">The imported schedule will be assigned to this faculty member and display automatically.</small>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label font-weight-600 text-dark">Select Excel / CSV File <span class="text-danger">*</span></label>
                        <input type="file" id="csv_file" name="csv_file" class="form-control form-control-lg" accept=".csv, .xlsx, .xls, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, text/csv" required>
                        <small class="text-muted d-block mt-1">Accepted formats: <strong>.xlsx, .xls</strong> (Excel Workbooks) or <strong>.csv</strong> (Comma-separated)</small>
                    </div>

                    <div class="p-3 bg-light rounded-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="replace_existing" name="replace_existing" value="1" checked>
                            <label class="form-check-label font-weight-500 text-dark" for="replace_existing">
                                Clear / Overwrite current timetable slots for this faculty before importing
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600 shadow-sm">
                        <i class="fas fa-upload me-1"></i> Upload & Apply Timetable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

