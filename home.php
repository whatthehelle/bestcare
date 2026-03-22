<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="home.css">
    <style>
          @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
          @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');  
    </style>
    <title>BestCare</title>
</head>
<body>
    
    <div class="sidebar">
        
        <div class="container">

            <section class="logo">
                <img src="img/logo.png" alt="bclogo" class="bclogo">
                
                <div class="brand-name">
                    <h1>BestCare</h1>
                    <h4>Online Appointment System (OAS)</h4>
                </div>
                
            </section>
        </div>

        <nav class="sidebar-nav">
            <ul>

                <li><a href="home.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="appointment.php">Request Appointment</a></li>
                <li><a href="records.php">Request Medical Documents</a></li>
                <li><a href="viewconsult.php">View Consultation Request</a></li>
                <li><a href="viewapt.php">View Appointments</a></li>
                <li><a href="">Logout</a></li>

            </ul>
        </nav>

    </div>

    <div class="main">

        <header class="topbar">
            <h1>Home</h1>
        </header>

        <div class="main-content">

            <div class="welcome-banner">
                <div class="welcome-content">
                    <h2>Greetings, Our Valued Patient</h2>
                    <p>Here's a summary of your health activities.</p>
                </div>
                
            </div>

            <div class="appointment-card">
                <div class="card-container">
                    <div class="card-header">
                        <h4>Appointment Card</h4>
                        <i><p class="hint">(This is your next appointment)</p></i>
                        <hr>
                    </div>
                    
                    <div class="card-content">
                        <h5>Appointment Reference Number: </h5>
                        <h5>Date of consultation: </h5>
                        <h5>Appointment Status: </h5>
                        <h5>Main Complaint: </h5>
                        <h5>Clinic: </h5>
                    </div>
                </div>
                    
            </div>

        </div>

    </div>

</body>
</html>