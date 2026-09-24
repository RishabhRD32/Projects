<?php
session_start();
include('connect.php');

$total_votes = $_POST['gvotes'] + 1;
$gid = $_POST['gid'];
$uid = $_SESSION['userdata']['id'];

// Update group votes using a prepared statement
$stmt1 = mysqli_prepare($con, "UPDATE user SET votes=? WHERE id=?");
mysqli_stmt_bind_param($stmt1, "ii", $total_votes, $gid);
$update_votes = mysqli_stmt_execute($stmt1);
mysqli_stmt_close($stmt1);

// Update user status using a prepared statement
$stmt2 = mysqli_prepare($con, "UPDATE user SET status=1 WHERE id=?");
mysqli_stmt_bind_param($stmt2, "i", $uid);
$update_user_status = mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

if($update_votes && $update_user_status){
    // Fetch updated group data
    $groups_query = mysqli_query($con, "SELECT * FROM user WHERE role=2");
    $groupsdata = mysqli_fetch_all($groups_query, MYSQLI_ASSOC);

    $_SESSION["userdata"]['status'] = 1;
    $_SESSION["groupsdata"] = $groupsdata;

    echo "<script>alert('Voting Successful!'); window.location = '../Routes/dashboard.php';</script>";
} else {
    echo "<script>alert('Some error occurred!'); window.location = '../Routes/dashboard.php';</script>";
}
?>