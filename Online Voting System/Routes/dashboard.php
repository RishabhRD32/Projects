<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["userdata"])) {
    header("location:../");
    exit();
}

$userdata = $_SESSION['userdata'];
$groupsdata = $_SESSION['groupsdata'];

if ($userdata['status'] == 0) {
    $status = '<b style="color:red">Not Voted</b>';
} else {
    $status = '<b style="color:green">Voted</b>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f2f5, #e0e8f0);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            transition: background 2s ease-in-out;
        }

        .main-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .headerSection {
            text-align: center;
            padding: 20px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .headerSection h1 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #3498db;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            margin: 0;
            flex-grow: 1;
        }
        
        .headerSection a button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        
        #backbtn {
            background-color: #f39c12; /* A warm color for 'Back' */
        }
        
        #logoutbtn {
            background-color: #e74c3c; /* A red color for 'Logout' */
        }

        .dashboard-content {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        #profile {
            background-color: #ffffff;
            width: 300px;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        #profile:hover {
            transform: translateY(-5px);
        }

        #profile img {
            border-radius: 50%;
            border: 4px solid #3498db;
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
        }

        #profile b, .group-card b {
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        #group-list {
            background-color: #ffffff;
            flex-grow: 1;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            min-height: 400px;
        }

        .group-card {
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: background-color 0.3s ease;
        }
        
        .group-card:last-child {
            border-bottom: none;
        }
        
        .group-card:hover {
            background-color: #f8f9fa;
        }

        .group-card img {
            border-radius: 10px;
            border: 2px solid #bdc3c7;
        }

        .group-info {
            flex-grow: 1;
        }
        
        .group-buttons {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        #votebtn, #voted {
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        #votebtn {
            background-color: #2ecc71; /* Green for 'Vote' */
        }

        #voted {
            background-color: #95a5a6; /* Grey for 'Voted' */
            cursor: not-allowed;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-content {
                flex-direction: column;
            }
            #profile {
                width: 100%;
                margin-bottom: 20px;
            }
            .headerSection {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="headerSection">
        <a href="../"><button id="backbtn">Back</button></a>
        <h1>Online Voting System</h1>
        <a href="logout.php"><button id="logoutbtn">Logout</button></a>
    </div>

    <div class="dashboard-content">
        <div id="profile">
            <center>
                <img src="../uploads/<?php echo $userdata['photo']; ?>" height="100" width="100" />
            </center>
            <br>
            <b>Name:</b> <?php echo $userdata['name']; ?><br><br>
            <b>Email:</b> <?php echo $userdata['email']; ?><br><br>
            <b>Mobile:</b> <?php echo $userdata['mobile']; ?><br><br>
            <b>Address:</b> <?php echo $userdata['address']; ?><br><br>
            <b>Status:</b> <?php echo $status; ?><br><br>
        </div>

        <div id="group-list">
            <?php
            if (isset($_SESSION['groupsdata'])) {
                foreach ($groupsdata as $group) {
            ?>
                    <div class="group-card">
                        <img src="../uploads/<?php echo $group['photo']; ?>" height="100" width="100">
                        <div class="group-info">
                            <b>Group Name: </b><?php echo $group['name']; ?><br><br>
                            <b>Votes: </b><?php echo $group['votes']; ?><br><br>
                        </div>
                        <div class="group-buttons">
                            <form action="../API/vote.php" method="POST">
                                <input type="hidden" name="gvotes" value="<?php echo $group['votes']; ?>">
                                <input type="hidden" name="gid" value="<?php echo $group['id']; ?>">
                                <?php
                                if ($userdata['status'] == 0) {
                                ?>
                                    <input type="submit" name="votebtn" value="Vote" id="votebtn">
                                <?php
                                } else {
                                ?>
                                    <button disabled type="button" name="votebtn" id="voted">Voted</button>
                                <?php
                                }
                                ?>
                            </form>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo "No groups available.";
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>