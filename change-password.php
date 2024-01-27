<?php
session_start(); 
include "contact.php";

if (isset($_POST['password'])) {
    if(empty($_POST['password']) && $_SESSION['TYPE'] === "1"){
        header("Location: profile-admin.php?error=Password is required");
	    exit();
	}else if(empty($_POST['password']) && $_SESSION['TYPE'] === "0"){
        header("Location: profile-user.php?error=Password is required");
	    exit();
	}else{
        $password = $_POST['password'];
        $username = $_SESSION['USERNAME'];
        $sql1 = "SELECT * FROM users WHERE pword='$password' AND username='$username'";

        $result = mysqli_query($conn, $sql1);
        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            if ($row['pword'] === $password && $_SESSION['TYPE'] === "1") {
                header("Location: profile-admin.php?error=Already you have same password.");
                exit();
            }else if ($row['pword'] === $password && $_SESSION['TYPE'] === "0") {
                header("Location: profile-user.php?error=Already you have same password.");
                exit();
            }
        }


        $id=$_SESSION['ID'];
        $sql2 = "UPDATE users SET pword=? WHERE userid=$id";
        $stmt= $conn->prepare($sql2);
        $stmt->bind_param('s', $password);
        $stmt->execute();

        if($stmt && $_SESSION['TYPE'] === "1"){
            $_SESSION['PASSWORD'] = $password;
            header("Refresh:1; url=profile-admin.php");
            exit();
        }else if($stmt && $_SESSION['TYPE'] === "0"){
            $_SESSION['PASSWORD'] = $password;
            header("Refresh:1; url=profile-user.php");
            exit();
        }else{
            echo"error";
        }
	}
}else if($_SESSION['TYPE'] === "1"){
	header("Location: profile-admin.php");
	exit();
}else if($_SESSION['TYPE'] === "0"){
	header("Location: profile-user.php");
	exit();
}