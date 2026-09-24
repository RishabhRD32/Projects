<?php
session_start();
include 'db.php';

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $pass    = $_POST['pass']          ?? '';
    $confirm = $_POST['confirm']       ?? '';
    $address = trim($_POST['address'] ?? '');

    if (!$name || !$email || !$phone || !$pass || !$address) {
        $error = "All fields are required.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone must be 10 digits.";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $ins = $conn->prepare("INSERT INTO users (name,email,phone,password,address,role) VALUES (?,?,?,?,?,'customer')");
            $ins->bind_param("sssss", $name, $email, $phone, $pass, $address);
            if ($ins->execute()) {
                $success = "Account created! Redirecting to login...";
                header("Refresh: 2; url=login.php");
            } else {
                $error = "Registration failed: " . $ins->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Foodie – Sign Up</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--gold:#D4AF37;--lead:#1a1a2e;--soft:#f7f3eb}
body{background:var(--soft);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;padding:20px}
.card{background:#fff;width:100%;max-width:460px;border-radius:20px;padding:40px;box-shadow:0 20px 60px rgba(0,0,0,.1)}
.logo{font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);display:block;text-align:center;margin-bottom:6px}
h2{text-align:center;font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--lead);margin-bottom:4px}
.sub{text-align:center;color:#999;font-size:.83rem;margin-bottom:22px}
.msg{padding:10px 14px;border-radius:8px;font-size:.85rem;margin-bottom:16px}
.msg.err{background:#fff0f0;border-left:3px solid #e74c3c;color:#c0392b}
.msg.ok {background:#f0fff4;border-left:3px solid #27ae60;color:#1e8449}
label{display:block;font-size:.74rem;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
input{width:100%;padding:10px 14px;border:1.5px solid #e8e8e8;border-radius:9px;font-size:.9rem;font-family:'DM Sans',sans-serif;transition:.2s;margin-bottom:13px}
input:focus{border-color:var(--gold);outline:none;background:#fffbe6}
.btn{width:100%;padding:13px;background:var(--lead);color:var(--gold);border:none;border-radius:9px;font-size:1rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:.2s;margin-top:4px}
.btn:hover{background:var(--gold);color:#fff}
.foot{text-align:center;margin-top:14px;font-size:.82rem;color:#999}
.foot a{color:var(--gold);font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <span class="logo">Foodie.</span>
  <h2>Create Account</h2>
  <p class="sub">Join us and get food delivered fast</p>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form method="POST">
    <label>Full Name</label>
    <input type="text" name="name" placeholder="John Doe" required>
    <label>Email Address</label>
    <input type="email" name="email" placeholder="you@example.com" required>
    <label>Phone (10 digits)</label>
    <input type="tel" name="phone" placeholder="9876543210" maxlength="10" required>
    <label>Password</label>
    <input type="password" name="pass" placeholder="Create a password" required>
    <label>Confirm Password</label>
    <input type="password" name="confirm" placeholder="Repeat password" required>
    <label>Delivery Address</label>
    <input type="text" name="address" placeholder="e.g. Room 5, MG Road, Mumbai - 400001" required>
    <button class="btn" type="submit">CREATE ACCOUNT</button>
  </form>
  <p class="foot">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>