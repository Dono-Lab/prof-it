<?php
session_start();
require_once '../config/config.php';
require_once 'get_captcha.php';

$captchaData = getCaptcha($conn);
$_SESSION['captcha_question'] = $captchaData['question'];
$_SESSION['captcha_id'] = $captchaData['id'];

header('Content-Type: application/json');
echo json_encode([
    'question' => $captchaData['question'],
    'id' => $captchaData['id']
]);
