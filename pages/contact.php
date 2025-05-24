<?php
require_once '../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - Printing Press</title>
  <!-- <link rel="stylesheet" href="../assets/css/login.css"> -->
  <style>
    body {
      font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f7fa;
      /* Soft blue-gray background */
      color: #333;
      padding-top: 76px;
    }

    nav {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      /* Professional blue gradient */
      padding: 18px 0;
      width: 100%;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    nav ul {
      list-style: none;
      display: flex;
      justify-content: center;
      padding: 0;
      margin: 0;
      gap: 25px;
    }

    nav ul li a {
      color: white;
      text-decoration: none;
      font-size: 17px;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    nav ul li a:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }

    nav ul li.login a {
      background-color: rgba(255, 255, 255, 0.25);
      padding: 8px 20px;
      border-radius: 20px;
    }

    .contact-container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .contact-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      margin-top: 2rem;
      justify-content: center;
    }

    .contact-info {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 40%;
      min-width: 300px;
    }

    .contact-info h2 {
      color: #333;
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }

    .info-item {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .info-item i {
      font-size: 1.5rem;
      color: #007bff;
      margin-right: 1rem;
      width: 30px;
    }

    .info-item p {
      margin: 0;
      color: #666;
      line-height: 1.6;
    }

    .contact-form {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 40%;
      min-width: 300px;
    }

    .contact-form h2 {
      color: #333;
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #555;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
    }

    .form-group textarea {
      height: 150px;
      resize: vertical;
    }

    .submit-btn {
      background: #007bff;
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.3s ease;
    }

    .submit-btn:hover {
      background: #0056b3;
    }

    .map-container {
      margin-top: 2rem;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .map-container iframe {
      width: 100%;
      height: 400px;
      border: 0;
    }

    .social-links {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .social-links a {
      color: #007bff;
      font-size: 1.5rem;
      transition: color 0.3s ease;
    }

    .social-links a:hover {
      color: #0056b3;
    }

    @media (max-width: 768px) {
      .contact-grid {
        flex-direction: column;
        align-items: center;
      }

      .contact-info,
      .contact-form {
        width: 100%;
        max-width: 100%;
      }
    }

    body::-webkit-scrollbar {
      width: 10px;
    }

    body::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.15);
      border-radius: 5px;
    }

    body::-webkit-scrollbar-track {
      background: transparent;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <nav>
    <ul>
      <li class="login"><a href="../index.php">Home</a></li>
      <li class="login"><a href="templets.php">Templates</a></li>
      <li class="login"><a href="contact.php">Contact</a></li>
      <li class="login"><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <div class="contact-container">
    <h1 style="text-align: center; color: #333; margin-bottom: 2rem;">Contact Us</h1>

    <div class="contact-grid">
      <div class="contact-info">
        <h2>Get in Touch</h2>
        <div class="info-item">
          <i class="fas fa-map-marker-alt"></i>
          <p>Morang, Nepal<br>Rangeli Road, Biratnagar</p>
        </div>
        <div class="info-item">
          <i class="fas fa-phone"></i>
          <p>+977 9842066784<br>+977 9818000000</p>
        </div>
        <div class="info-item">
          <i class="fas fa-envelope"></i>
          <p>info@printingpress.com<br>support@printingpress.com</p>
        </div>
        <div class="info-item">
          <i class="fas fa-clock"></i>
          <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM</p>
        </div>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin"></i></a>
        </div>
      </div>

      <div class="contact-form">
        <h2>Send us a Message</h2>
        <form action="../modules/process_contact.php" method="POST">
          <div class="form-group">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required>
          </div>
          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" required></textarea>
          </div>
          <button type="submit" class="submit-btn">Send Message</button>
        </form>
      </div>
    </div>

    <div class="map-container">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3571.92505330925!2d87.28144407513022!3d26.458144679478178!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef7438d1af98b9%3A0x5e48ab62121d9745!2z4KSw4KSC4KSX4KWH4KSy4KWAIOCksOCli-CkoSwg4KS14KS_4KSw4KS-4KSf4KSo4KSX4KSwIDU2NjEz!5e0!3m2!1sne!2snp!4v1746389674784!5m2!1sne!2snp"
        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</body>

</html>