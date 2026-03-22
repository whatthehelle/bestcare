<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="viewapt.css">    
    <style>
          @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
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
            <h1>Consultations</h1>
        </header>

        <div class="main-content">

            <div class="main-container">

                <h3 class="h3title">View Consultation Requests</h3>
                <h5 class="h5title">(Monitor the status of your consultation requests.)</h5>

                <div class="view-body">

                    <div class="tab-row">
                        <button class="tab-btn active" onclick="switchTab('active', this)">Active</button>
                        <button class="tab-btn" onclick="switchTab('resolved', this)">Resolved</button>
                    </div>

                    <div class="tab-panel" id="tab-active">
                        <h5 class="formtitle">Active Consultation Requests</h5>
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th>Date Requested</th>
                                    <th>Status</th>
                                    <th>Staff Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Persistent headache for 3 days</td>
                                    <td>Jan 10, 2025</td>
                                    <td><span class="badge badge-pending">Pending</span></td>
                                    <td><span class="note-waiting">—</span></td>
                                </tr>
                                <tr>
                                    <td>Fever and cough</td>
                                    <td>Jan 12, 2025</td>
                                    <td><span class="badge badge-approved">Approved</span></td>
                                    <td><span class="note-ready">Appointment scheduled for Jan 15.</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-panel" id="tab-resolved" style="display:none;">
                        <h5 class="formtitle">Resolved Consultations</h5>
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th>Date Requested</th>
                                    <th>Status</th>
                                    <th>Staff Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Annual physical exam</td>
                                    <td>Dec 01, 2024</td>
                                    <td><span class="badge badge-completed">Completed</span></td>
                                    <td><span class="note-ready">All results normal.</span></td>
                                </tr>
                                <tr>
                                    <td>Back pain</td>
                                    <td>Nov 20, 2024</td>
                                    <td><span class="badge badge-cancelled">Cancelled</span></td>
                                    <td><span class="note-waiting">—</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
        function switchTab(tab, btn) {
            document.querySelectorAll('.tab-panel').forEach(function(p) {
                p.style.display = 'none';
            });
            document.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            document.getElementById('tab-' + tab).style.display = 'block';
            btn.classList.add('active');
        }
    </script>

</body>
</html>