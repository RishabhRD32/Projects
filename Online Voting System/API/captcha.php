<?php
$response = $_POST['g-recaptcha-response'];
$secret   = "6Le0_dArAAAAAJ2VWwsDuHKLtbv7Lx0B7mO_0Cbz";

$verify = file_get_contents(
  "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response"
);
$captcha_success = json_decode($verify);

if ($captcha_success->success) {
    echo "✅ Human Verified!";
} else {
    echo "❌ Failed CAPTCHA!";
}
?>