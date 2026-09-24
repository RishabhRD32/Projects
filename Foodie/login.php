<?php
session_start();
include 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        $stmt = $conn->prepare("SELECT id, name, phone, address, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && $pass === $row['password']) {
            $_SESSION['user_id']      = $row['id'];
            $_SESSION['user_name']    = $row['name'];
            $_SESSION['user_phone']   = $row['phone']   ?? '';
            $_SESSION['user_address'] = $row['address'] ?? '';
            $_SESSION['role']         = $row['role'];
            header("Location: " . ($row['role'] === 'admin' ? 'admin.php' : 'index.php'));
            exit;
        }
        $error = "Invalid email or password.";
    } else {
        $error = "Please enter email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Foodie – Login</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--gold:#D4AF37;--lead:#1a1a2e;--soft:#f7f3eb}
body{background:var(--soft);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;padding:20px}
.wrap{display:flex;width:820px;border-radius:24px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.left{background:var(--lead);flex:1;padding:50px 40px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
.left::before{content:'';position:absolute;width:300px;height:300px;background:rgba(212,175,55,.08);border-radius:50%;right:-80px;top:-80px}
.left::after{content:'';position:absolute;width:200px;height:200px;background:rgba(212,175,55,.05);border-radius:50%;left:-50px;bottom:-50px}
.logo{font-family:'Playfair Display',serif;font-size:2.5rem;color:var(--gold);margin-bottom:14px}
.left h2{font-family:'Playfair Display',serif;font-size:1.5rem;color:#fff;margin-bottom:12px;line-height:1.4}
.left p{color:#aaa;font-size:.88rem;line-height:1.7}
.hint{background:rgba(255,255,255,.06);border:1px solid rgba(212,175,55,.2);border-radius:10px;padding:14px 16px;margin-top:22px}
.hint p{color:#bbb;font-size:.78rem;line-height:2;margin:0}
.hint strong{color:var(--gold)}
.right{background:#fff;flex:1;padding:50px 40px;display:flex;flex-direction:column;justify-content:center}
.right h3{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--lead);margin-bottom:4px}
.right .sub{color:#999;font-size:.85rem;margin-bottom:26px}
.error{background:#fff0f0;border-left:3px solid #e74c3c;color:#c0392b;padding:10px 14px;border-radius:8px;font-size:.85rem;margin-bottom:18px}
label{display:block;font-size:.75rem;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
input{width:100%;padding:12px 15px;border:1.5px solid #e8e8e8;border-radius:9px;font-size:.93rem;font-family:'DM Sans',sans-serif;transition:.2s;margin-bottom:16px}
input:focus{border-color:var(--gold);outline:none;background:#fffbe6}
.btn{width:100%;padding:14px;background:var(--lead);color:var(--gold);border:none;border-radius:9px;font-size:1rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;letter-spacing:.5px;transition:.2s}
.btn:hover{background:var(--gold);color:#fff}
.foot{text-align:center;margin-top:18px;font-size:.82rem;color:#999}
.foot a{color:var(--gold);font-weight:700;text-decoration:none}
@media(max-width:640px){.wrap{flex-direction:column;width:100%;max-width:420px}.left{padding:30px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="left">
    <div class="logo">Foodie.</div>
    <h2>Delicious food, delivered fast 🍔</h2>
    <p>Login to place orders and track your deliveries in real time.</p>
    <div class="hint">
      <p><strong>Admin</strong> → admin@foodie.com / admin123<br>
         <strong>Customer</strong> → register via Sign Up</p>
    </div>
  </div>
  <div class="right">
    <h3>Welcome Back</h3>
    <p class="sub">Sign in to continue</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" placeholder="you@example.com" required autofocus>
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <button class="btn" type="submit">LOGIN</button>
    </form>
    <p class="foot">New here? <a href="signup.php">Create an account</a></p>
  </div>
</div>
</body>
</html>