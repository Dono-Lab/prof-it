<?php
require_once '../config/config.php';

function getCaptcha($conn)
{
    $stmt = $conn->prepare("SELECT id, question FROM captcha_questions WHERE actif = 1 ORDER BY RAND () LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verifyCaptcha($conn, $captchaId, $UserAnswer)
{
    $stmt = $conn->prepare("SELECT reponse FROM captcha_questions WHERE id = ?");
    $stmt->execute([$captchaId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false;
    }

    $correctAnswer = strtolower(trim($result['reponse']));
    $UserAnswer = strtolower(trim($UserAnswer));

    return $correctAnswer === $UserAnswer;
}
