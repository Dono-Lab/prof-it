<?php
require_once __DIR__ . '/includes/helpers.php';
safe_session_start();

if (is_logged_in()) {
    if (is_student()) {
        header("Location: student/student_page.php");
        exit;
    }

    if (is_teacher()) {
        header("Location: teacher/teacher_page.php");
        exit;
    }

    if (is_admin()) {
        header("Location: admin/dashboard.php");
        exit;
    }
}

header("Location: public/home.html");
exit;
