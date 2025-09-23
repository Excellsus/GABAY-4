<?php
// session_start();
// require_once '/var/www/html/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - GABAY</title>
  <link rel="stylesheet" href="about.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <header class="header">
    <div class="header-content">
      <h2 class="section-title">About</h2>
      <p class="section-subtitle">Information about this application.</p>
    </div>
    <div class="header-actions">
      <a href="feedback.php" aria-label="Provide Feedback"><i class="fas fa-comment-alt"></i></a>
    </div>
  </header>

  <!-- Main content area -->
  <main class="content">
  <section class="about-section">
    <div class="about-card">
      <h3>What is GABAY?</h3>
      <p>
        <strong>GABAY</strong> is a digital visitor guidance and navigation system developed for the Negros Occidental Provincial Capitol. 
        It helps guests, employees, and newcomers find offices, navigate the building using interactive maps, and leave feedback for continuous improvement.
      </p>
    </div>

    <div class="about-card">
      <h3>Purpose</h3>
      <p>
        The goal of GABAY is to enhance transparency, accessibility, and convenience in public service by offering an intuitive platform that makes navigating 
        government offices easier and more efficient.
      </p>
    </div>

    <div class="about-card team-section">
      <h3>Created By</h3>

      <div class="team-member">
        <div class="member-photo" style="background-image: url('img/project-manager.jpg');"></div>
        <div class="member-info">
          <h4>Obligado, Adrian G. <span class="role-label">Project Manager</span></h4>
          <p>Permi ga cramming kag Gusto na mag Untat pro waay man gina himo ang ulobrahon temprano.</p>
        </div>
      </div>

      <div class="team-member">
        <div class="member-photo" style="background-image: url('img/system-analyst.jpg');"></div>
        <div class="member-info">
          <h4>Quimzon, Earl John A. <span class="role-label">System Analyst</span></h4>
          <p>Broom Broom Broom. Basta Broom Broom Broom Ahhhhh.</p>
        </div>
      </div>

      <div class="team-member">
        <div class="member-photo" style="background-image: url('img/lead-programmer.jpg');"></div>
        <div class="member-info">
          <h4>Javier, John Joseph C. <span class="role-label">Lead Programmer</span></h4>
          <p>Batak! waay ga buya sa kamot ang selpon pro kong chatton waay ga reply biskan tawgan!! LALA.</p>
        </div>
      </div>

      <div class="team-member">
        <div class="member-photo" style="background-image: url('img/assistant-programmer.jpg');"></div>
        <div class="member-info">
          <h4>Tiad, Azriel Jed T. <span class="role-label">Assistant Programmer</span></h4>
          <p>Gina tuyo permi pro waay man ga tulog, kanugon kuno sang Oras kong itulog.</p>
        </div>
      </div>
    </div>

    <div class="about-card">
      <h3>Message from the Creator</h3>
      <p>
        "We built GABAY to serve as a bridge between the public and our local government. My vision is to make our institutions more approachable and 
        efficient using technology. Thank you for using GABAY — I hope it guides you well."
      </p>
    </div>
  </section>
</main>


  <!-- Bottom Navigation Section -->
  <nav class="bottom-nav" role="navigation" aria-label="Visitor navigation">
    <a href="explore.php" aria-label="Explore">
      <i class="fas fa-map-marker-alt"></i>
      <span>Explore</span>
    </a>

    <a href="rooms.php" aria-label="Rooms">
      <i class="fas fa-building"></i>
      <span>Rooms</span>
    </a>

    <a href="about.php" class="active" aria-label="About">
      <i class="fas fa-bars"></i>
      <span>About</span>
    </a>
  </nav>
</body>
</html>
