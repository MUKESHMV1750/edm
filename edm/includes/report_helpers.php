<?php
/**
 * Report Helper Functions
 * Engineering Department Management System
 */

require_once __DIR__ . '/functions.php';

// ══════════════════════════════════════════════════════
// Attendance Reports
// ══════════════════════════════════════════════════════

/**
 * Get attendance summary for a student
 */
function getStudentAttendanceSummary($studentId, $subjectId = null, $dateFrom = null, $dateTo = null) {
    $pdo = getDBConnection();
    $sql = "SELECT 
                s.code AS subject_code,
                s.name AS subject_name,
                COUNT(ar.id) AS total_classes,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) AS late_count,
                ROUND(SUM(CASE WHEN ar.status IN ('present','late') THEN 1 ELSE 0 END) / COUNT(ar.id) * 100, 1) AS percentage
            FROM attendance_records ar
            JOIN attendance_sessions asess ON ar.session_id = asess.id
            JOIN subjects s ON asess.subject_id = s.id
            WHERE ar.student_id = :sid";
    $params = [':sid' => $studentId];

    if ($subjectId) {
        $sql .= " AND asess.subject_id = :subj";
        $params[':subj'] = $subjectId;
    }
    if ($dateFrom) {
        $sql .= " AND asess.session_date >= :from";
        $params[':from'] = $dateFrom;
    }
    if ($dateTo) {
        $sql .= " AND asess.session_date <= :to";
        $params[':to'] = $dateTo;
    }

    $sql .= " GROUP BY s.id ORDER BY s.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get class attendance for a staff member's subject
 */
function getClassAttendanceReport($staffId, $subjectId, $semester, $section, $dateFrom = null, $dateTo = null) {
    $pdo = getDBConnection();
    $sql = "SELECT 
                st.register_number,
                CONCAT(st.first_name, ' ', st.last_name) AS student_name,
                COUNT(ar.id) AS total,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent,
                ROUND(SUM(CASE WHEN ar.status IN ('present','late') THEN 1 ELSE 0 END) / COUNT(ar.id) * 100, 1) AS percentage
            FROM students st
            JOIN attendance_records ar ON ar.student_id = st.id
            JOIN attendance_sessions asess ON ar.session_id = asess.id
            WHERE asess.staff_id = :staffId
              AND asess.subject_id = :subj
              AND st.semester = :sem
              AND st.section = :sec";
    $params = [':staffId' => $staffId, ':subj' => $subjectId, ':sem' => $semester, ':sec' => $section];

    if ($dateFrom) {
        $sql .= " AND asess.session_date >= :from";
        $params[':from'] = $dateFrom;
    }
    if ($dateTo) {
        $sql .= " AND asess.session_date <= :to";
        $params[':to'] = $dateTo;
    }

    $sql .= " GROUP BY st.id ORDER BY st.register_number";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ══════════════════════════════════════════════════════
// Marks Reports
// ══════════════════════════════════════════════════════

/**
 * Get student's marks for all subjects
 */
function getStudentMarksReport($studentId, $academicYear = null) {
    $pdo = getDBConnection();
    $year = $academicYear ?? CURRENT_ACADEMIC_YEAR;

    $stmt = $pdo->prepare(
        "SELECT 
            s.code AS subject_code,
            s.name AS subject_name,
            im.exam_type,
            im.max_marks,
            im.obtained_marks,
            ROUND(im.obtained_marks / im.max_marks * 100, 1) AS percentage,
            im.exam_date
        FROM internal_marks im
        JOIN subjects s ON im.subject_id = s.id
        WHERE im.student_id = :sid AND im.academic_year = :year
        ORDER BY s.name, im.exam_type"
    );
    $stmt->execute([':sid' => $studentId, ':year' => $year]);
    return $stmt->fetchAll();
}

/**
 * Get subject-wise marks summary for staff
 */
function getSubjectMarksReport($subjectId, $examType, $academicYear = null) {
    $pdo = getDBConnection();
    $year = $academicYear ?? CURRENT_ACADEMIC_YEAR;

    $stmt = $pdo->prepare(
        "SELECT 
            st.register_number,
            CONCAT(st.first_name, ' ', st.last_name) AS student_name,
            st.section,
            im.max_marks,
            im.obtained_marks,
            ROUND(im.obtained_marks / im.max_marks * 100, 1) AS percentage
        FROM internal_marks im
        JOIN students st ON im.student_id = st.id
        WHERE im.subject_id = :subj
          AND im.exam_type = :type
          AND im.academic_year = :year
        ORDER BY st.register_number"
    );
    $stmt->execute([':subj' => $subjectId, ':type' => $examType, ':year' => $year]);
    return $stmt->fetchAll();
}

// ══════════════════════════════════════════════════════
// Assignment Reports
// ══════════════════════════════════════════════════════

function getAssignmentSubmissionReport($assignmentId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT 
            st.register_number,
            CONCAT(st.first_name, ' ', st.last_name) AS student_name,
            asub.submitted_at,
            asub.is_late,
            asub.marks,
            asub.status,
            a.max_marks
        FROM students st
        LEFT JOIN assignment_submissions asub ON asub.student_id = st.id AND asub.assignment_id = :aid
        JOIN assignments a ON a.id = :aid2
        WHERE st.department_id = a.department_id
          AND st.semester = a.semester
          AND (a.section IS NULL OR st.section = a.section)
        ORDER BY st.register_number"
    );
    $stmt->execute([':aid' => $assignmentId, ':aid2' => $assignmentId]);
    return $stmt->fetchAll();
}

// ══════════════════════════════════════════════════════
// Department Summary (Admin)
// ══════════════════════════════════════════════════════

function getDepartmentSummary($departmentId = null) {
    $pdo = getDBConnection();
    $sql = "SELECT 
                d.name AS department,
                d.code,
                (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS total_students,
                (SELECT COUNT(*) FROM staff sf WHERE sf.department_id = d.id) AS total_staff,
                (SELECT COUNT(*) FROM subjects sub WHERE sub.department_id = d.id AND sub.status = 'active') AS total_subjects
            FROM departments d
            WHERE d.status = 'active'";
    $params = [];

    if ($departmentId) {
        $sql .= " AND d.id = :did";
        $params[':did'] = $departmentId;
    }

    $sql .= " ORDER BY d.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ══════════════════════════════════════════════════════
// CSV Export Helper
// ══════════════════════════════════════════════════════

function exportToCSV($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $headers);

    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }

    fclose($output);
    exit;
}

// ══════════════════════════════════════════════════════
// Report Metadata
// ══════════════════════════════════════════════════════

function getReportMeta($title, $filters = []) {
    return [
        'title'        => $title,
        'generated_at' => date('d M Y, h:i:s A'),
        'generated_by' => getCurrentUserName(),
        'filters'      => $filters,
        'app_name'     => APP_NAME,
    ];
}
