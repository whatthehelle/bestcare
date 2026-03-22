<?php
session_start();

require_once 'database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $firstname = trim($_POST["firstname"]);
    $middlename = trim($_POST["middlename"]);
    $lastname = trim($_POST["lastname"]);
    $birthday = trim($_POST["birthday"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirmpass = trim($_POST["confirmpass"]);
   
    if ($password !== $confirmpass) {
        $errors[] = "Password do not match.";
    }

    if (empty($firstname) || empty($middlename) || empty($lastname) || empty($birthday) || empty($username) || empty($password) || empty($confirmpass)) {
        $errors[] = "All fields are required.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!preg_match('/[!@#$%^&*(),.?]/', $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    if (strtotime($birthday) > time()) {
        $errors[] = "Birthday cannot be in the future.";
    }

    $age = date('Y') - date('Y', strtotime($birthday));
    if (date('md') < date('md', strtotime($birthday))) {
        $age--;
    }

    if ($age < 18) {
        $errors[] = "You must be at least 18 years old.";
    }

    if (strlen($username) < 5 || !preg_match('/^\w+$/', $username)) {
        $errors[] = "Username must be at least 5 characters, letters, numbers, and underscores only.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: register.php');
        exit;
    }

    if (empty($errors)) {
        $stmt = $mysql->prepare('SELECT patient_id FROM patientregistration WHERE username = ? LIMIT 1');
        $stmt->bind_param('s',$username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
    
            $_SESSION['errors'] = ["Username is already taken. Please choose another."];
    
        } else {

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $mysql->prepare("INSERT INTO patientregistration (username, password, firstname, middlename, lastname, birthday) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashedPassword, $firstname, $middlename, $lastname, $birthday);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! You can now log in.";
                header('Location: login.php');
                exit;
            } else {
                $errors['submit'] = 'Database error. Please try again.';
                header('Location: register.php');
                exit;
            }
        }
        $stmt->close();
    }
}    
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="register.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');    
    </style> 

    <title>BestCare</title>
</head>
<body>
    
    <section class="header">
        <div class="container">
            <section class="logo">
                <img src="img/logo.png" alt="bclogo" class="bclogo">
                <div class="brand-name">
                    <h1>BestCare</h1>
                    <h4>Online Appointment System (OAS)</h4>
                </div>
                
            </section>
        </div>
    </section>

    <section class="register-page">

        <div class="register-form">
            <div class="container">
                <h3>Register</h3>
                <form method="post" id="register-paper" class="register-paper">

                    <p id="perinfo">Personal Information</p>
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname">
                        
                    
                    <label for="middlename">Middle Name:</label>
                    <input type="text" id="middlename" name="middlename">
                                    
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname">     
                    

                    <label for="birthdaytext">Birthday:</label>
                    <input type="date" id="birthday" name="birthday">
                        

                    <p>Credentials to log-in</p>
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username">
                    

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password">
                        

                    <label for="confirmpass">Confirm Password:</label>
                    <input type="password" id="confirmpass" name="confirmpass">
                        

                    <button type="submit" id="submitreg" class="register-btn">Register</button>
                        
                                                
                    <label for="resspan"></label>
                    <span id="resspan"></span>

                </form>
            </div>
        </div>
    </section>
    
    <script>

        let savedInputs = [];
        
        const today = new Date();
        const maxYear = today.getFullYear() - 18;
        const maxMonth = String(today.getMonth() + 1).padStart(2, '0');
        const maxDay = String(today.getDate()).padStart(2, '0');

        document.getElementById("birthday").max = `${maxYear}-${maxMonth}-${maxDay}`;

        document.getElementById("register-paper").addEventListener("submit", function(event){
            let fname = document.getElementById("firstname").value;
            let mname = document.getElementById("middlename").value;
            let lname = document.getElementById("lastname").value;
            let bday = new Date (document.getElementById("birthday").value);
            let username = document.getElementById("username").value;
            let password = document.getElementById("password").value;
            let confirmpass = document.getElementById("confirmpass").value;

            resspan.style.color = "red";

            if (fname === "" || mname === "" || lname === "" || bday === "" || username === "" || password === "" || confirmpass === "") {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "All fields are required.";
                return;
            }

            if (password !== confirmpass) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "Password does not match.";
                return;
            }
            
            if (!/[!@#$%^&*(),.?":{}<>|]/.test(password&&confirmpass)) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "Password must contain at least one special character.";

            }

            if (bday > new Date()) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "Birthday cannot be in the future.";
                return;
            }

            let today = new Date();
            let age = today.getFullYear() - bday.getFullYear();
            if (age < 18) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "You must be at least 18 years old.";
                return;
            }

            if (username.length < 5) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "Username must be at least 5 characters long.";
                return;
            }

            if (!/^\w+$/.test(username)) {
                event.preventDefault();
                document.getElementById("resspan").innerHTML = "Username must contain only letters, numbers, and underscores.";
                return;
            }

        });

    </script>


</body>
</html>