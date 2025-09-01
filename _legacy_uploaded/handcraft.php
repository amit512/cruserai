<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HandCraft - Handmade Marketplace</title>
<!--  <link rel="stylesheet" href="handcraft.css"/>-->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>

  <!-- Header Start -->
  <header class="main-header">
    <div class="logo"><span>Hand</span>Craft</div>
    
    <nav class="nav-links">
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
      </ul>
    </nav>

    <div class="header-icons">
      <a href="#"><i class="fas fa-search"></i></a>
      <a href="login.php" class="btn login">Login</a>
      <a href="register.php" class="btn register">Register</a>
    </div>
  </header>
  <!-- Header End -->

  <!-- Hero Section Start -->
  <section class="hero" id="home">
    <div class="hero-content">
      <h1>Created with Love,<br>Shared with Care</h1>
      <p>Discover unique handmade treasures from talented artisans around the world. Every piece tells a story of passion, creativity, and dedication.</p>
      <div class="hero-buttons">
        <a href="buyer-dashboard.php" class="btn shop"> Start Shopping</a>
        <a href="seller-dashboard.php" class="btn seller"> Become a Seller</a>
      </div>
    </div>
    <div class="hero-image">
      <img src="H1.avif" alt="Handcrafting Illustration">
    </div>
  </section>
  <!-- Hero Section End -->

  <!-- About Us Section Start -->
  <section class="about-section" id="about">
    <div class="about-container">
      <div class="about-content">
        <h2>About HandCraft</h2>
        <div class="quote-section">
          <blockquote>"Authenticity in every stitch, spark, and stroke."</blockquote>
        </div>
        <p>HandCraft was born from a simple belief: that every handmade piece carries the soul of its creator. We're more than just a marketplace - we're a community where artisans share their passion and customers discover authentic, one-of-a-kind treasures.</p>
        
        <div class="about-features">
          <div class="feature">
            <i class="fas fa-heart"></i>
            <h3>Made with Love</h3>
            <p>Every item is crafted with passion and attention to detail.</p>
          </div>
          
          <div class="feature">
            <i class="fas fa-globe"></i>
            <h3>Global Community</h3>
            <p>Connect with artisans from around the world.</p>
          </div>
          
          <div class="feature">
            <i class="fas fa-leaf"></i>
            <h3>Sustainable</h3>
            <p>Supporting eco-friendly and ethical craftsmanship.</p>
          </div>
        </div>
      </div>
      
      <div class="about-stats">
        <div class="stat">
          <h3>5,000+</h3>
          <p>Artisans</p>
        </div>
        <div class="stat">
          <h3>25,000+</h3>
          <p>Products</p>
        </div>
        <div class="stat">
          <h3>50+</h3>
          <p>Countries</p>
        </div>
      </div>
    </div>
  </section>
  <!-- About Us Section End -->

  <!-- How It Works Section Start -->
  <section class="how-it-works-section">
    <div class="how-it-works-container">
      <h2>How It Works</h2>
      <p>Simple steps to buy and sell handmade treasures</p>
      
      <div class="steps-container">
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-user-plus"></i>
          </div>
          <h3>Create Account</h3>
          <p>Sign up as a buyer or seller in minutes</p>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-search"></i>
          </div>
          <h3>Browse or List</h3>
          <p>Find unique items or showcase your creations</p>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <h3>Buy or Sell</h3>
          <p>Secure transactions with buyer protection</p>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-star"></i>
          </div>
          <h3>Rate & Review</h3>
          <p>Build trust through community feedback</p>
        </div>
      </div>
    </div>
  </section>
  <!-- How It Works Section End -->

  <!-- Product Showcase Section Start -->
  <section class="showcase-section">
    <div class="showcase-container">
      <h2>Featured Handcrafted Treasures</h2>
      <p>Discover the latest creations from our talented artisans</p>
      
      <div class="showcase-grid">
        <div class="showcase-item">
          <div class="item-image">
            <img src="https://via.placeholder.com/300x200/f39c12/ffffff?text=Pottery" alt="Handcrafted Pottery">
          </div>
          <div class="item-info">
            <h3>Handcrafted Ceramic Vase</h3>
            <p>by Maria's Pottery Studio</p>
            <div class="item-price">$89.99</div>
            <div class="item-rating">★★★★★ (127 reviews)</div>
          </div>
        </div>
        
        <div class="showcase-item">
          <div class="item-image">
            <img src="https://via.placeholder.com/300x200/3498db/ffffff?text=Textiles" alt="Handwoven Scarf">
          </div>
          <div class="item-info">
            <h3>Handwoven Cotton Scarf</h3>
            <p>by Textile Traditions</p>
            <div class="item-price">$45.00</div>
            <div class="item-rating">★★★★☆ (89 reviews)</div>
          </div>
        </div>
        
        <div class="showcase-item">
          <div class="item-image">
            <img src="https://via.placeholder.com/300x200/2ecc71/ffffff?text=Woodwork" alt="Wooden Jewelry Box">
          </div>
          <div class="item-info">
            <h3>Oak Wood Jewelry Box</h3>
            <p>by Oak & Pine Crafts</p>
            <div class="item-price">$125.00</div>
            <div class="item-rating">★★★★★ (203 reviews)</div>
          </div>
        </div>
      </div>
      
      <div class="showcase-cta">
        <a href="buyer-dashboard.php" class="btn-primary">View All Products</a>
      </div>
    </div>
  </section>
  <!-- Product Showcase Section End -->

  <!-- Why HandCraft Section Start -->
  <section class="why-handcraft-section">
    <div class="why-handcraft-container">
      <h2>Why Choose HandCraft?</h2>
      
      <div class="reasons-grid">
        <div class="reason">
          <i class="fas fa-certificate"></i>
          <h3>Authenticity</h3>
          <p>Every item is verified as genuinely handmade by our community of artisans.</p>
        </div>
        
        <div class="reason">
          <i class="fas fa-palette"></i>
          <h3>Creativity</h3>
          <p>Discover unique designs that reflect the artist's personal style and vision.</p>
        </div>
        
        <div class="reason">
          <i class="fas fa-seedling"></i>
          <h3>Sustainability</h3>
          <p>Support eco-friendly practices and reduce environmental impact.</p>
        </div>
        
        <div class="reason">
          <i class="fas fa-hands-helping"></i>
          <h3>Community</h3>
          <p>Connect directly with artisans and support their craft and livelihood.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- Why HandCraft Section End -->

  <!-- Call-to-Action Section Start -->
  <section class="cta-section">
    <div class="cta-container">
      <h2>Ready to Join Our Community?</h2>
      <p>Whether you're looking to discover unique handmade treasures or share your craft with the world, HandCraft is your perfect platform.</p>
      <div class="cta-buttons">
        <a href="buyer-dashboard.php" class="btn-white">🛍️ Start Shopping</a>
        <a href="seller-dashboard.php" class="btn-outline">👨‍🎨 Become a Seller</a>
      </div>
    </div>
  </section>
  <!-- Call-to-Action Section End -->

  <!-- Footer Start -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section">
        <h4>HandCraft</h4>
        <p>Connecting artisans with craft enthusiasts worldwide. Every piece tells a story.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-pinterest"></i></a>
        </div>
      </div>
      
      <div class="footer-section">
        <h4>Quick Links</h4>
        <a href="#home">Home</a>
        <a href="#about">About Us</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#contact">Contact</a>
      </div>
      
      <div class="footer-section">
        <h4>For Artisans</h4>
        <a href="seller-dashboard.php">Become a Seller</a>
        <a href="#">Seller Guidelines</a>
        <a href="#">Tutorials</a>
        <a href="#">Community</a>
      </div>
      
      <div class="footer-section">
        <h4>Support</h4>
        <a href="#">Help Center</a>
        <a href="#">Contact Us</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>&copy; 2024 HandCraft. All rights reserved. | Crafted with ❤️ for handmade lovers</p>
    </div>
  </footer>
  <!-- Footer End -->

</body>
</html>
