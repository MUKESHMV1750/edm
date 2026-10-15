<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Handle Create Student
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $register_number = sanitizeInput($_POST['register_number']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $department_id = (int)$_POST['department_id'];
        $semester = (int)$_POST['semester'];
        $section = sanitizeInput($_POST['section']);
        $phone = sanitizeInput($_POST['phone']);

        try {
            $db->beginTransaction();

            $insU = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
            $insU->execute([$username, $email, $password]);
            $userId = $db->lastInsertId();

            $insS = $db->prepare("
                INSERT INTO students (user_id, register_number, first_name, last_name, phone, department_id, semester, section, academic_year, admission_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, '2024-2025', CURDATE())
            ");
            $insS->execute([$userId, $register_number, $first_name, $last_name, $phone, $department_id, $semester, $section]);

            $db->commit();
            setFlashMessage('success', 'Student account created successfully!');
            redirect('admin/manage_students.php');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('danger', 'Error creating student: ' . $e->getMessage());
        }
    }
}

// Handle Edit Student
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $stId = (int)$_POST['student_id'];
        $email = sanitizeInput($_POST['email']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $register_number = sanitizeInput($_POST['register_number']);
        $department_id = (int)$_POST['department_id'];
        $semester = (int)$_POST['semester'];
        $section = sanitizeInput($_POST['section']);
        $phone = sanitizeInput($_POST['phone']);

        try {
            $db->beginTransaction();

            $stUsr = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stUsr->execute([$stId]);
            $uId = $stUsr->fetchColumn();

            if ($uId) {
                $db->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $uId]);
            }

            $updSt = $db->prepare("
                UPDATE students 
                SET first_name = ?, last_name = ?, register_number = ?, department_id = ?, semester = ?, section = ?, phone = ?
                WHERE id = ?
            ");
            $updSt->execute([$first_name, $last_name, $register_number, $department_id, $semester, $section, $phone, $stId]);

            $db->commit();
            setFlashMessage('success', 'Student details updated successfully!');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('danger', 'Error updating student: ' . $e->getMessage());
        }
        redirect('admin/manage_students.php');
    }
}

// Delete Student
if ((($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') || isset($_GET['delete'])) {
    $stId = (int)($_POST['student_id'] ?? $_GET['delete'] ?? 0);
    $stUsr = $db->prepare("SELECT user_id FROM students WHERE id = ?");
    $stUsr->execute([$stId]);
    $uId = $stUsr->fetchColumn();

    if ($uId) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uId]);
        setFlashMessage('success', 'Student account removed successfully.');
    }
    redirect('admin/manage_students.php');
}

// Handle Export Students to CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $exportStmt = $db->query("
        SELECT s.register_number, s.first_name, s.last_name, u.username, u.email, 
               d.code AS dept_code, d.name AS dept_name, s.semester, s.section, 
               s.phone, s.academic_year, s.admission_date
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN departments d ON s.department_id = d.id
        ORDER BY s.register_number ASC
    ");
    $exportStudents = $exportStmt->fetchAll();

    $filename = 'students_export_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers
    fputcsv($out, [
        'Register No',
        'First Name',
        'Last Name',
        'Full Name',
        'Username',
        'Email',
        'Department Code',
        'Department Name',
        'Semester',
        'Section',
        'Phone',
        'Academic Year',
        'Admission Date'
    ]);

    foreach ($exportStudents as $st) {
        fputcsv($out, [
            $st['register_number'],
            $st['first_name'],
            $st['last_name'],
            trim($st['first_name'] . ' ' . $st['last_name']),
            $st['username'],
            $st['email'],
            $st['dept_code'],
            $st['dept_name'],
            $st['semester'],
            $st['section'],
            $st['phone'],
            $st['academic_year'],
            $st['admission_date']
        ]);
    }
    fclose($out);
    exit;
}

// Handle Download Sample Import Template
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $filename = 'student_import_template.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, [
        'Register No',
        'First Name',
        'Last Name',
        'Username',
        'Email',
        'Initial Password',
        'Department Code',
        'Semester',
        'Section',
        'Phone'
    ]);

    // Sample data rows
    fputcsv($out, [
        'CSE2024101',
        'John',
        'Doe',
        'john.doe',
        'john.doe@edms.edu',
        'student123',
        'CSE',
        '4',
        'A',
        '9876543210'
    ]);
    fputcsv($out, [
        'ECE2024102',
        'Jane',
        'Smith',
        'jane.smith',
        'jane.smith@edms.edu',
        'student123',
        'ECE',
        '4',
        'B',
        '9876543211'
    ]);

    fclose($out);
    exit;
}

// Handle Import Students CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Invalid security token. Please try again.');
        redirect('admin/manage_students.php');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        setFlashMessage('danger', 'Please upload a valid CSV file.');
        redirect('admin/manage_students.php');
    }

    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        setFlashMessage('danger', 'Invalid file format. Only .csv files are supported.');
        redirect('admin/manage_students.php');
    }

    $handle = fopen($fileTmpPath, 'r');
    if ($handle === false) {
        setFlashMessage('danger', 'Failed to open uploaded CSV file.');
        redirect('admin/manage_students.php');
    }

    // Build department lookup map
    $allDepts = $db->query("SELECT id, code, name FROM departments")->fetchAll();
    $deptMap = [];
    foreach ($allDepts as $d) {
        $deptMap[strtoupper(trim($d['code']))] = (int)$d['id'];
        $deptMap[strtoupper(trim($d['name']))] = (int)$d['id'];
        $deptMap[(string)$d['id']] = (int)$d['id'];
    }
    $defaultDeptId = !empty($allDepts) ? (int)$allDepts[0]['id'] : 1;

    // Read header line
    $headerRow = fgetcsv($handle);
    if ($headerRow === false) {
        fclose($handle);
        setFlashMessage('danger', 'Uploaded CSV file appears to be empty.');
        redirect('admin/manage_students.php');
    }

    // Map header column names to normalized keys
    $colMap = [];
    foreach ($headerRow as $idx => $headerName) {
        $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim($headerName));
        $cleaned = strtolower(str_replace([' ', '_', '-', '/', '.'], '', $cleaned));

        if (in_array($cleaned, ['registerno', 'regno', 'registernumber', 'reg', 'studentregisterno', 'studentregno', 'studentregister'])) {
            $colMap['register_number'] = $idx;
        } elseif (in_array($cleaned, ['firstname', 'fname', 'first'])) {
            $colMap['first_name'] = $idx;
        } elseif (in_array($cleaned, ['lastname', 'lname', 'last'])) {
            $colMap['last_name'] = $idx;
        } elseif (in_array($cleaned, ['fullname', 'studentname', 'name'])) {
            $colMap['full_name'] = $idx;
        } elseif (in_array($cleaned, ['username', 'user', 'uname'])) {
            $colMap['username'] = $idx;
        } elseif (in_array($cleaned, ['email', 'emailaddress', 'mail'])) {
            $colMap['email'] = $idx;
        } elseif (in_array($cleaned, ['password', 'initialpassword', 'pass'])) {
            $colMap['password'] = $idx;
        } elseif (in_array($cleaned, ['departmentcode', 'deptcode', 'department', 'dept', 'departmentname', 'deptname'])) {
            $colMap['department'] = $idx;
        } elseif (in_array($cleaned, ['semester', 'sem'])) {
            $colMap['semester'] = $idx;
        } elseif (in_array($cleaned, ['section', 'sec'])) {
            $colMap['section'] = $idx;
        } elseif (in_array($cleaned, ['phone', 'phonenumber', 'mobile', 'contact'])) {
            $colMap['phone'] = $idx;
        } elseif (in_array($cleaned, ['academicyear', 'acadyear', 'year'])) {
            $colMap['academic_year'] = $idx;
        } elseif (in_array($cleaned, ['admissiondate', 'admitdate', 'dateofadmission'])) {
            $colMap['admission_date'] = $idx;
        }
    }

    // Default indices if headers could not be matched
    if (!isset($colMap['register_number'])) $colMap['register_number'] = 0;
    if (!isset($colMap['first_name'])) $colMap['first_name'] = 1;
    if (!isset($colMap['last_name'])) $colMap['last_name'] = 2;
    if (!isset($colMap['username'])) $colMap['username'] = (count($headerRow) >= 13 ? 4 : 3);
    if (!isset($colMap['email'])) $colMap['email'] = (count($headerRow) >= 13 ? 5 : 4);
    if (!isset($colMap['department'])) $colMap['department'] = (count($headerRow) >= 13 ? 6 : 6);
    if (!isset($colMap['semester'])) $colMap['semester'] = (count($headerRow) >= 13 ? 8 : 7);
    if (!isset($colMap['section'])) $colMap['section'] = (count($headerRow) >= 13 ? 9 : 8);
    if (!isset($colMap['phone'])) $colMap['phone'] = (count($headerRow) >= 13 ? 10 : 9);

    $importedCount = 0;
    $updatedCount = 0;
    $skippedCount = 0;
    $skippedReasons = [];
    $rowNumber = 1;

    // Track used usernames and emails in this batch to prevent in-batch collision
    $batchUsernames = [];
    $batchEmails = [];

    $checkUserStmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $checkEmailStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkRegStmt = $db->prepare("SELECT id, user_id FROM students WHERE register_number = ? LIMIT 1");
    $insertUserStmt = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
    $insertStudentStmt = $db->prepare("
        INSERT INTO students (user_id, register_number, first_name, last_name, phone, department_id, semester, section, academic_year, admission_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $updateStudentStmt = $db->prepare("
        UPDATE students 
        SET first_name = ?, last_name = ?, department_id = ?, semester = ?, section = ?,
            phone = COALESCE(NULLIF(?, ''), phone),
            academic_year = COALESCE(NULLIF(?, ''), academic_year),
            admission_date = COALESCE(NULLIF(?, ''), admission_date)
        WHERE id = ?
    ");

    $isUsernameTaken = function($u) use ($checkUserStmt, &$batchUsernames) {
        if (in_array(strtolower($u), $batchUsernames)) return true;
        $checkUserStmt->execute([$u]);
        return (bool)$checkUserStmt->fetchColumn();
    };

    $isEmailTaken = function($e) use ($checkEmailStmt, &$batchEmails) {
        if (in_array(strtolower($e), $batchEmails)) return true;
        $checkEmailStmt->execute([$e]);
        return (bool)$checkEmailStmt->fetchColumn();
    };

    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if (empty(array_filter($row))) {
            continue;
        }

        $regNo = trim($row[$colMap['register_number']] ?? '');
        $firstName = trim($row[$colMap['first_name']] ?? '');
        $lastName = trim($row[$colMap['last_name']] ?? '');
        $fullName = isset($colMap['full_name']) ? trim($row[$colMap['full_name']] ?? '') : '';

        if (empty($firstName) && !empty($fullName)) {
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
        }

        if (empty($regNo)) {
            $skippedCount++;
            if (count($skippedReasons) < 5) {
                $skippedReasons[] = "Row {$rowNumber}: Missing Register Number";
            }
            continue;
        }

        if (empty($firstName)) {
            $firstName = 'Student';
            $lastName = $regNo;
        }

        $regClean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $regNo));

        $rawUsername = isset($colMap['username']) ? trim($row[$colMap['username']] ?? '') : '';
        $rawEmail = isset($colMap['email']) ? trim($row[$colMap['email']] ?? '') : '';
        $password = isset($colMap['password']) ? trim($row[$colMap['password']] ?? '') : '';
        if (empty($password)) {
            $password = 'student123';
        }

        $deptVal = trim($row[$colMap['department']] ?? '');
        $deptId = $deptMap[strtoupper($deptVal)] ?? $defaultDeptId;

        $semVal = (int)($row[$colMap['semester']] ?? 1);
        if ($semVal < 1 || $semVal > 20) {
            $semVal = 1;
        }

        $secVal = strtoupper(trim($row[$colMap['section']] ?? 'A'));
        if (empty($secVal)) {
            $secVal = 'A';
        }

        $phoneVal = trim($row[$colMap['phone']] ?? '');

        $rawAcadYear = isset($colMap['academic_year']) ? trim($row[$colMap['academic_year']] ?? '') : '';
        $acadYear = !empty($rawAcadYear) ? sanitizeInput($rawAcadYear) : '2024-2025';

        // Parse Admission Date
        $rawDate = isset($colMap['admission_date']) ? trim($row[$colMap['admission_date']] ?? '') : '';
        $admissionDate = null;
        if (!empty($rawDate)) {
            $dt = DateTime::createFromFormat('d-m-Y', $rawDate);
            if ($dt) {
                $admissionDate = $dt->format('Y-m-d');
            } else {
                $ts = strtotime($rawDate);
                if ($ts !== false) {
                    $admissionDate = date('Y-m-d', $ts);
                }
            }
        }
        if (!$admissionDate) {
            $admissionDate = date('Y-m-d');
        }

        // Check if student with this Register Number already exists in database
        $checkRegStmt->execute([$regNo]);
        $existingStudent = $checkRegStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingStudent) {
            // Update existing student details
            try {
                $updateStudentStmt->execute([
                    $firstName,
                    $lastName,
                    $deptId,
                    $semVal,
                    $secVal,
                    $phoneVal,
                    $acadYear,
                    $admissionDate,
                    $existingStudent['id']
                ]);
                $updatedCount++;
            } catch (Exception $e) {
                $skippedCount++;
                if (count($skippedReasons) < 5) {
                    $skippedReasons[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
            continue;
        }

        // New Student: Resolve unique username
        $candidateUser = !empty($rawUsername) ? strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $rawUsername)) : $regClean;
        if ($isUsernameTaken($candidateUser)) {
            // If raw username is already taken or duplicate, use clean register number
            if (!$isUsernameTaken($regClean)) {
                $candidateUser = $regClean;
            } else {
                $sfx = 1;
                while ($isUsernameTaken($regClean . '_' . $sfx)) {
                    $sfx++;
                }
                $candidateUser = $regClean . '_' . $sfx;
            }
        }
        $batchUsernames[] = $candidateUser;

        // Resolve unique email
        $candidateEmail = !empty($rawEmail) ? strtolower($rawEmail) : ($candidateUser . '@edms.edu');
        if ($isEmailTaken($candidateEmail)) {
            $defaultEmail = $candidateUser . '@edms.edu';
            if (!$isEmailTaken($defaultEmail)) {
                $candidateEmail = $defaultEmail;
            } else {
                $sfx = 1;
                while ($isEmailTaken($candidateUser . $sfx . '@edms.edu')) {
                    $sfx++;
                }
                $candidateEmail = $candidateUser . $sfx . '@edms.edu';
            }
        }
        $batchEmails[] = $candidateEmail;

        // Insert new user and student account
        try {
            $db->beginTransaction();
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $insertUserStmt->execute([$candidateUser, $candidateEmail, $passHash]);
            $newUserId = $db->lastInsertId();

            $insertStudentStmt->execute([
                $newUserId,
                $regNo,
                $firstName,
                $lastName,
                $phoneVal,
                $deptId,
                $semVal,
                $secVal,
                $acadYear,
                $admissionDate
            ]);
            $db->commit();
            $importedCount++;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $skippedCount++;
            if (count($skippedReasons) < 5) {
                $skippedReasons[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
    }
    fclose($handle);

    $totalProcessed = $importedCount + $updatedCount;
    if ($totalProcessed > 0) {
        $msgParts = [];
        if ($importedCount > 0) $msgParts[] = "{$importedCount} new student accounts registered";
        if ($updatedCount > 0) $msgParts[] = "{$updatedCount} existing student records updated";
        $msg = "Import completed successfully: " . implode(', ', $msgParts) . "!";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} rows skipped: " . implode('; ', $skippedReasons) . ")";
        }
        setFlashMessage('success', $msg);
    } else {
        $msg = "No student accounts were imported.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} rows skipped: " . implode('; ', $skippedReasons) . ")";
        }
        setFlashMessage('warning', $msg);
    }
    redirect('admin/manage_students.php');
}

// Fetch Departments
$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

// Fetch Students
$students = $db->query("
    SELECT s.*, u.username, u.email, d.code AS dept_code, d.name AS dept_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    ORDER BY s.id DESC
")->fetchAll();

$pageTitle = "Manage Students";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Student Account Management</h1>
                    <p class="text-muted small mb-0">Add, update, import, or export student accounts across departments</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="?action=export_csv" class="btn btn-outline-success rounded-pill btn-sm px-3 font-weight-600" title="Export All Students to CSV">
                        <i class="fas fa-file-export me-1"></i> Export CSV
                    </a>
                    <button type="button" class="btn btn-outline-primary rounded-pill btn-sm px-3 font-weight-600" data-bs-toggle="modal" data-bs-target="#importStudentsModal" title="Import Students from CSV">
                        <i class="fas fa-file-import me-1"></i> Import CSV
                    </button>
                    <button type="button" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#newStudentModal">
                        <i class="fas fa-user-plus me-1"></i> Add Student
                    </button>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Quick Filter Bar -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark font-weight-600 px-3 py-2 border rounded-pill">
                            <i class="fas fa-users text-primary me-1"></i> <?= count($students) ?> Students Enrolled
                        </span>
                        <span class="badge bg-light text-muted font-weight-500 px-3 py-2 border rounded-pill">
                            <i class="fas fa-building me-1"></i> <?= count($departments) ?> Departments
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="studentTableSearch" class="form-control form-control-sm rounded-pill px-3" placeholder="Quick search students..." style="max-width: 260px;" onkeyup="filterStudentsTable()">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Username / Email</th>
                                <th>Dept</th>
                                <th>Sem / Sec</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No students found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                        <td class="small">
                                            <div><strong><?= htmlspecialchars($s['username']) ?></strong></div>
                                            <div class="text-muted"><?= htmlspecialchars($s['email']) ?></div>
                                        </td>
                                        <td><span class="badge bg-warning-light text-dark font-weight-600"><?= htmlspecialchars($s['dept_code']) ?></span></td>
                                        <td>Sem <?= $s['semester'] ?> (<?= htmlspecialchars($s['section']) ?>)</td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Student Details" aria-label="View"
                                                        onclick='viewStudent(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Student Account" aria-label="Edit"
                                                        onclick='editStudent(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Delete Student" aria-label="Delete"
                                                        onclick="deleteStudent(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])) ?>', '<?= htmlspecialchars(addslashes($s['register_number'])) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: New Student -->
<div class="modal fade" id="newStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-user-plus text-warning me-2"></i> Register New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label font-weight-500">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label font-weight-500">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label font-weight-500">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="e.g. john.d" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label font-weight-500">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="john@edms.edu" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="register_number" class="form-label font-weight-500">Register Number</label>
                            <input type="text" id="register_number" name="register_number" class="form-control" placeholder="CSE2024099" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label font-weight-500">Initial Password</label>
                            <input type="password" id="password" name="password" class="form-control" value="student123" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="department_id" class="form-label font-weight-500">Department</label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="semester" class="form-label font-weight-500">Semester</label>
                            <select id="semester" name="semester" class="form-select" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == 4 ? 'selected' : '' ?>>Sem <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="section" class="form-label font-weight-500">Section</label>
                            <select id="section" name="section" class="form-select" required>
                                <option value="Batch A">Batch A</option>
                                <option value="Batch B">Batch B</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label font-weight-500">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="9876543210" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Register Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Student -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-user-graduate text-primary me-2"></i> Student Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-warning-light text-dark font-weight-bold px-3 py-2 fs-6 me-3" id="viewStudentReg"></span>
                    <div>
                        <h5 class="h6 font-weight-bold text-dark mb-0" id="viewStudentName"></h5>
                        <small class="text-muted" id="viewStudentEmail"></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Username:</span>
                        <strong class="text-dark" id="viewStudentUsername"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Department:</span>
                        <span class="badge bg-primary text-white" id="viewStudentDept"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Semester & Section:</span>
                        <span class="badge bg-light text-dark font-weight-600" id="viewStudentSemSec"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Phone Number:</span>
                        <span class="text-dark font-weight-500" id="viewStudentPhone"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Student -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-user-edit text-warning me-2"></i> Edit Student Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="editStudentId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStudentFirstName" class="form-label font-weight-500">First Name</label>
                            <input type="text" id="editStudentFirstName" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentLastName" class="form-label font-weight-500">Last Name</label>
                            <input type="text" id="editStudentLastName" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStudentRegInput" class="form-label font-weight-500">Register Number</label>
                            <input type="text" id="editStudentRegInput" name="register_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentEmailInput" class="form-label font-weight-500">Email Address</label>
                            <input type="email" id="editStudentEmailInput" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="editStudentDeptInput" class="form-label font-weight-500">Department</label>
                            <select id="editStudentDeptInput" name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="editStudentSemInput" class="form-label font-weight-500">Semester</label>
                            <select id="editStudentSemInput" name="semester" class="form-select" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="editStudentSecInput" class="form-label font-weight-500">Section</label>
                            <select id="editStudentSecInput" name="section" class="form-select" required>
                                <option value="Batch A">Batch A</option>
                                <option value="Batch B">Batch B</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editStudentPhoneInput" class="form-label font-weight-500">Phone Number</label>
                        <input type="text" id="editStudentPhoneInput" name="phone" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delete Student -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Delete Student Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="student_id" id="deleteStudentId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to permanently delete student <strong id="deleteStudentName"></strong> (<strong id="deleteStudentReg"></strong>)?</p>
                    <small class="text-danger d-block mt-2">Warning: All attendance records, submissions, and marks for this student will be permanently deleted.</small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-weight-600">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Import Students CSV -->
<div class="modal fade" id="importStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-file-import text-primary me-2"></i> Import Students via CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 p-3 mb-4 bg-info-light">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-info-circle text-info me-1"></i> CSV Format Guidelines</h6>
                                <p class="small text-muted mb-0">Upload a CSV file containing your student roster. Recommended column headers:</p>
                                <code class="small text-dark d-block mt-1 font-weight-600">Register No, First Name, Last Name, Username, Email, Initial Password, Department Code, Semester, Section, Phone</code>
                            </div>
                            <a href="?action=download_template" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600 px-3">
                                <i class="fas fa-download me-1"></i> Sample Template
                            </a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label font-weight-600 text-dark">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" id="csv_file" name="csv_file" class="form-control form-control-lg" accept=".csv" required>
                        <small class="text-muted d-block mt-1">Accepted file type: <strong>.csv</strong> (Max size: 5MB)</small>
                    </div>

                    <div class="row g-3 p-3 bg-light rounded-3 mt-1">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Default Initial Password</small>
                            <span class="badge bg-secondary text-white font-weight-600">student123</span>
                            <small class="text-muted ms-1">(applied if blank in CSV)</small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Available Department Codes</small>
                            <span class="small font-weight-600 text-dark">
                                <?php 
                                $deptCodes = array_map(function($d) { return $d['code']; }, $departments);
                                echo htmlspecialchars(implode(', ', $deptCodes));
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600">
                        <i class="fas fa-upload me-1"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterStudentsTable() {
    const input = document.getElementById('studentTableSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#studentsTableBody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function viewStudent(s) {
    document.getElementById('viewStudentReg').innerText = s.register_number || '';
    document.getElementById('viewStudentName').innerText = (s.first_name || '') + ' ' + (s.last_name || '');
    document.getElementById('viewStudentEmail').innerText = s.email || '';
    document.getElementById('viewStudentUsername').innerText = s.username || '';
    document.getElementById('viewStudentDept').innerText = (s.dept_name || '') + ' (' + (s.dept_code || '') + ')';
    document.getElementById('viewStudentSemSec').innerText = 'Semester ' + (s.semester || '1') + ' • Section ' + (s.section || 'A');
    document.getElementById('viewStudentPhone').innerText = s.phone || 'N/A';
    new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
}

function editStudent(s) {
    document.getElementById('editStudentId').value = s.id;
    document.getElementById('editStudentFirstName').value = s.first_name || '';
    document.getElementById('editStudentLastName').value = s.last_name || '';
    document.getElementById('editStudentRegInput').value = s.register_number || '';
    document.getElementById('editStudentEmailInput').value = s.email || '';
    document.getElementById('editStudentDeptInput').value = s.department_id || '';
    document.getElementById('editStudentSemInput').value = s.semester || '1';
    document.getElementById('editStudentSecInput').value = s.section || 'A';
    document.getElementById('editStudentPhoneInput').value = s.phone || '';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(id, name, reg) {
    document.getElementById('deleteStudentId').value = id;
    document.getElementById('deleteStudentName').innerText = name;
    document.getElementById('deleteStudentReg').innerText = reg;
    new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
