<?php
// about.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - HandCraft</title>
  <link rel="stylesheet" href="handcraf.css"/>
  <link rel="stylesheet" href="startstyle.css"/>
  <link rel="stylesheet" href="about.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f5;
    }
    .about-hero {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      padding: 3rem 0;
      text-align: center;
    }
    .about-hero h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      font-weight: bold;
    }
    .about-hero span {
      color: #ffc107;
    }
    .about-hero p {
      font-size: 1.2rem;
      opacity: 0.9;
      margin: 0;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .our-story, .our-values, .our-team {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin: 2rem 0;
    }
    .our-story h2, .our-values h2, .our-team h2 {
      color: #4CAF50;
      margin-bottom: 1rem;
      font-size: 2rem;
      font-weight: bold;
    }
    .our-story p {
      color: #666;
      font-size: 1.1rem;
      line-height: 1.7;
      margin-bottom: 1rem;
    }
    .values-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }
    .value {
      background: #f9f9f9;
      border-radius: 10px;
      padding: 2rem 1rem;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .value i {
      font-size: 2.5rem;
      color: #4CAF50;
      margin-bottom: 1rem;
    }
    .value h3 {
      color: #333;
      margin-bottom: 0.5rem;
      font-size: 1.2rem;
    }
    .value p {
      color: #666;
      font-size: 1rem;
    }
    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }
    .team-member {
      background: #f9f9f9;
      border-radius: 10px;
      padding: 2rem 1rem;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .team-member img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #4CAF50;
      margin-bottom: 1rem;
    }
    .team-member h3 {
      color: #333;
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }
    .team-member p {
      color: #666;
      font-size: 1rem;
    }
    @media (max-width: 768px) {
      .about-hero h1 {
        font-size: 2rem;
      }
      .our-story, .our-values, .our-team {
        padding: 1rem;
      }
      .values-grid, .team-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <!-- About Hero -->
  <section class="about-hero">
    <div class="about-hero-content container">
      <h1>About <span>HandCraft</span></h1>
      <p>Connecting artisans with craft enthusiasts around the world.</p>
    </div>
  </section>

  <div class="container">
    <!-- Our Story -->
    <section class="our-story">
      <h2>Our Story</h2>
      <p>
        HandCraft was born from a simple belief: that every handmade piece carries the soul of its creator.  
        We are more than just a marketplace — we are a vibrant community where artisans share their passion 
        and customers discover authentic, one-of-a-kind treasures.
      </p>
      <p>
        Since 2024, we’ve been on a mission to support small businesses, promote sustainable practices, 
        and celebrate creativity worldwide.
      </p>
    </section>

    <!-- Our Values -->
    <section class="our-values">
      <h2>Our Values</h2>
      <div class="values-grid">
        <div class="value">
          <i class="fas fa-heart"></i>
          <h3>Passion</h3>
          <p>Every item is made with dedication, love, and creativity.</p>
        </div>
        <div class="value">
          <i class="fas fa-globe"></i>
          <h3>Global Community</h3>
          <p>Connecting artisans and buyers from over 50 countries.</p>
        </div>
        <div class="value">
          <i class="fas fa-seedling"></i>
          <h3>Sustainability</h3>
          <p>We promote eco-friendly and ethical craftsmanship.</p>
        </div>
        <div class="value">
          <i class="fas fa-handshake"></i>
          <h3>Trust</h3>
          <p>We ensure safe transactions and fair opportunities for all.</p>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="our-team">
      <h2>Meet Our Team</h2>
      <div class="team-grid">
        <div class="team-member">
          <img src="./uploads/received_1241326006304908.jpeg" alt="Founder">
          <h3>OM PRAKASH</h3>
          <p>Founder & CEO</p>
        </div>
        <div class="team-member">
          <img src="https://via.placeholder.com/200" alt="Co-Founder">
          <h3>AKASH BOHARA</h3>
          <p>Co-Founder & CTO</p>
        </div>
        <div class="team-member">
          <img src="https://via.placeholder.com/200" alt="Community Manager">
          <h3>BASMATI CHAUDHARY</h3>
          <p>Community Manager</p>
        </div>
      </div>
    </section>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
