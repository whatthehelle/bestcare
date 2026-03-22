<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="index.css">
   <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');    
    </style> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestCare Online Appointment System</title>
</head>
<body>
    
    <header>
        <div class="container">

            <section class="logo">
                <img src="img/logo.png" alt="bclogo" class="bclogo">
                
                <h4>BestCare</h4>
                        
            </section>

            <nav>
                <ul>
                    <li><a href="">Home</a></li>
                    <li><a href="">Services</a></li>
                    <li><a href="">Contact Us</a></li>
                </ul>
            </nav>        
        </div>

    </header>

    <section class="home">

        <img src="clinic-room.jpg" alt="Clinic Room">    

       <div class="home-content">    
            <div class="container">

                <h1>BestCare Online Appointment System (OAS)</h1>
                <p>Book your appointment with our healthcare professionals today.</p>

                <button id="request-btn" class="request-btn">Sign Up</button>

            </div>

        </div>

        
        
    </section>

    <section class="offer-part">
        <h2>BestCare Offers</h2>
         
        <div class="offer-slider">

            <div class="container">

                <div class="offer">
                    <div class="offer-logo">
                        <img src="img/people.png" alt="service 1">
                    </div>
                    <h4>Patient Management</h4>
                    <p>Efficiently manage your information, and appointments with our user-friendly interface.</p>
                </div>

                <div class="offer">
                    <div class="offer-logo">
                        <img src="img/calendar.png" alt="service 1">
                    </div>
                    <h4>Appointment Scheduling</h4>
                    <p> Easily schedule and manage your appointments with our intuitive booking system.</p>
                </div>

                <div class="offer">
                    <div class="offer-logo">
                        <img src="img/record.png" alt="service 1">
                    </div>
                    <h4>Medical Records</h4>
                    <p>Request and access your medical records securely and conveniently.</p>
                </div>


            </div>

        </div>

    </section>

    <section class="service-part">
        <h2>BestCare Services</h2>
         
        <div class="service-slider">

            <div class="container">

                <div class="service">
                    <div class="service-logo">
                        <img src="img/healthy.png" alt="service 1">
                    </div>
                    <h4>Annual Physical Exam</h4>
                </div>

                <div class="service">
                    <div class="service-logo">
                        <img src="img/tooth.png" alt="service 1">
                    </div>
                    <h4>Dental</h4>
                </div>

                <div class="service">
                    <div class="service-logo">
                        <img src="img/drugs.png" alt="service 1">
                    </div>
                    <h4>Drug Testing</h4>
                </div>
                
                <div class="service">
                    <div class="service-logo">
                        <img src="img/microscope.png" alt="service 1">
                    </div>
                    <h4>Laboratory</h4>
                </div>

                <div class="service">
                    <div class="service-logo">
                        <img src="img/peach.png" alt="service 1">
                    </div>
                    <h4>Nutrition Testing</h4>
                </div>

                <div class="service">
                    <div class="service-logo">
                        <img src="img/ultrasound.png" alt="service 1">
                    </div>
                    <h4>Ultrasound</h4>
                </div>

            </div>

        </div>

    </section>

    <section class="contact">

    </section>

    <script>

        document.getElementById("request-btn").addEventListener("click", function (event){
                window.location.href = "login.php";
        });


    </script>



</body>
</html>