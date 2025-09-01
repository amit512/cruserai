<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Seller Dashboard - HandCraft</title>
  <link rel="stylesheet" href="seller-dashboard.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <!-- Header -->
  <header class="top-header">
    <div class="header-left">
      <button class="menu-toggle" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
      </button>
    </div>
    <div class="header-right">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search products, orders...">
      </div>
      <button class="add-product-btn" onclick="openAddProductModal()">
        <i class="fas fa-plus"></i>
        Add Product
      </button>
      <div class="user-profile">
        <img src="https://via.placeholder.com/40" alt="Profile">
      </div>
    </div>
  </header>

  <!-- Popup Menu Overlay -->
  <div class="menu-overlay" id="menuOverlay" onclick="closeMenu()"></div>

  <!-- Popup Menu -->
  <nav class="popup-menu" id="popupMenu">
    <div class="menu-header">
      <h3> HandCraft</h3>
      <button class="menu-close" onclick="closeMenu()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <ul class="menu-items">
      <li><a href="#"><i class="fas fa-box"></i> Products</a></li>
      <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
      <li><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
      <li><a href="#"><i class="fas fa-users"></i> Customers</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>
  </nav>

  <!-- Add Product Modal -->
  <div class="modal-overlay" id="addProductModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="fas fa-plus"></i> Add New Product</h2>
        <button class="modal-close" onclick="closeAddProductModal()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form class="add-product-form" id="addProductForm">
        <div class="form-row">
          <div class="form-group">
            <label for="productName">Product Name *</label>
            <input type="text" id="productName" name="productName" required placeholder="Enter product name">
          </div>
          <div class="form-group">
            <label for="productCategory">Category *</label>
            <select id="productCategory" name="productCategory" required>
              <option value="">Select category</option>
              <option value="clothing">Clothing & Accessories</option>
              <option value="jewelry">Jewelry & Accessories</option>
              <option value="home">Home & Living</option>
              <option value="art">Art & Collectibles</option>
              <option value="beauty">Beauty & Personal Care</option>
              <option value="toys">Toys & Games</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="productPrice">Price (₹) *</label>
            <input type="number" id="productPrice" name="productPrice" required placeholder="0.00" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label for="productStock">Stock Quantity *</label>
            <input type="number" id="productStock" name="productStock" required placeholder="0" min="0">
          </div>
        </div>
        
        <div class="form-group">
          <label for="productDescription">Description *</label>
          <textarea id="productDescription" name="productDescription" required placeholder="Describe your product..." rows="4"></textarea>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="productMaterial">Material</label>
            <input type="text" id="productMaterial" name="productMaterial" placeholder="e.g., Cotton, Wood, Ceramic">
          </div>
          <div class="form-group">
            <label for="productWeight">Weight (g)</label>
            <input type="number" id="productWeight" name="productWeight" placeholder="0" min="0">
          </div>
        </div>
        
        <div class="form-group">
          <label for="productImages">Product Images</label>
          <div class="image-upload-area">
            <input type="file" id="productImages" name="productImages" multiple accept="image/*">
            <div class="upload-placeholder">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Click to upload images or drag and drop</p>
              <span>Maximum 5 images, each up to 5MB</span>
            </div>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="productTags">Tags</label>
            <input type="text" id="productTags" name="productTags" placeholder="handmade, eco-friendly, vintage (separate with commas)">
          </div>
          <div class="form-group">
            <label for="productSKU">SKU Code</label>
            <input type="text" id="productSKU" name="productSKU" placeholder="Auto-generated" readonly>
            <small class="form-help">SKU will be automatically generated when you save the product</small>
          </div>
        </div>
        
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" id="productActive" name="productActive" checked>
            <span class="checkmark"></span>
            Active (Available for sale)
          </label>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick="closeAddProductModal()">Cancel</button>
          <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i>
            Add Product
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal-overlay" id="editProductModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="fas fa-edit"></i> Edit Product</h2>
        <button class="modal-close" onclick="closeEditProductModal()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form class="edit-product-form" id="editProductForm">
        <div class="form-row">
          <div class="form-group">
            <label for="editProductName">Product Name *</label>
            <input type="text" id="editProductName" name="productName" required placeholder="Enter product name">
          </div>
          <div class="form-group">
            <label for="editProductCategory">Category *</label>
            <select id="editProductCategory" name="productCategory" required>
              <option value="">Select category</option>
              <option value="clothing">Clothing & Accessories</option>
              <option value="jewelry">Jewelry & Accessories</option>
              <option value="home">Home & Living</option>
              <option value="art">Art & Collectibles</option>
              <option value="beauty">Beauty & Personal Care</option>
              <option value="toys">Toys & Games</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="editProductPrice">Price (₹) *</label>
            <input type="number" id="editProductPrice" name="productPrice" required placeholder="0.00" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label for="editProductStock">Stock Quantity *</label>
            <input type="number" id="editProductStock" name="productStock" required placeholder="0" min="0">
          </div>
        </div>
        
        <div class="form-group">
          <label for="editProductDescription">Description *</label>
          <textarea id="editProductDescription" name="productDescription" required placeholder="Describe your product..." rows="4"></textarea>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="editProductMaterial">Material</label>
            <input type="text" id="editProductMaterial" name="productMaterial" placeholder="e.g., Cotton, Wood, Ceramic">
          </div>
          <div class="form-group">
            <label for="editProductWeight">Weight (g)</label>
            <input type="number" id="editProductWeight" name="productWeight" placeholder="0" min="0">
          </div>
        </div>
        
        <div class="form-group">
          <label for="editProductImages">Product Images</label>
          <div class="image-upload-area">
            <input type="file" id="editProductImages" name="productImages" multiple accept="image/*">
            <div class="upload-placeholder">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Click to upload new images or drag and drop</p>
              <span>Maximum 5 images, each up to 5MB</span>
            </div>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="editProductTags">Tags</label>
            <input type="text" id="editProductTags" name="productTags" placeholder="handmade, eco-friendly, vintage (separate with commas)">
          </div>
          <div class="form-group">
            <label for="editProductSKU">SKU Code</label>
            <input type="text" id="editProductSKU" name="productSKU" placeholder="Auto-generated" readonly>
            <small class="form-help">SKU cannot be changed once assigned</small>
          </div>
        </div>
        
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" id="editProductActive" name="productActive" checked>
            <span class="checkmark"></span>
            Active (Available for sale)
          </label>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick="closeEditProductModal()">Cancel</button>
          <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i>
            Update Product
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Main Content -->
  <main class="main-content">
    <div class="dashboard-content">
      <!-- Flash Alert Message -->
      <div class="flash-alert">
        <div class="alert-content">
          <i class="fas fa-info-circle"></i>
          <span>Welcome back! You have 3 new orders and 2 low stock alerts.</span>
        </div>
        <button class="alert-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

    <!-- Sales Ticker -->
    <div class="sales-ticker">
      <div class="ticker-container">
        <div class="ticker-track">
          <div class="ticker-item">
            <i class="fas fa-chart-line"></i>
            <span class="ticker-label">Today's Sales:</span>
            <span class="ticker-value">₹1,245</span>
          </div>
          <div class="ticker-item">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="ticker-label">Low Stock This Week:</span>
            <span class="ticker-value">3 items</span>
          </div>
          <div class="ticker-item">
            <i class="fas fa-shipping-fast"></i>
            <span class="ticker-label">To Ship:</span>
            <span class="ticker-value">2 orders</span>
          </div>
          <div class="ticker-item">
            <i class="fas fa-chart-line"></i>
            <span class="ticker-label">Today's Sales:</span>
            <span class="ticker-value">₹1,245</span>
          </div>
          <div class="ticker-item">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="ticker-label">Low Stock This Week:</span>
            <span class="ticker-value">3 items</span>
          </div>
          <div class="ticker-item">
            <i class="fas fa-shipping-fast"></i>
            <span class="ticker-label">To Ship:</span>
            <span class="ticker-value">2 orders</span>
          </div>
        </div>
      </div>
    </div>

      <!-- Stats Section -->
      <section class="stats-section">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="stat-info">
            <h3>₹12,450</h3>
            <p>Total Sales</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-box"></i>
          </div>
          <div class="stat-info">
            <h3>24</h3>
            <p>Products</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="stat-info">
            <h3>18</h3>
            <p>Orders</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-star"></i>
          </div>
          <div class="stat-info">
            <h3>4.8</h3>
            <p>Rating</p>
          </div>
        </div>
      </section>

      <!-- Recent Orders -->
      <section class="order-management">
        <div class="section-header">
          <h2>Recent Orders</h2>
          <div class="order-filters">
            <button class="filter-btn active">All</button>
            <button class="filter-btn">Pending</button>
            <button class="filter-btn">Shipped</button>
            <button class="filter-btn">Delivered</button>
          </div>
        </div>
        <div class="orders-table">
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>#HC001</td>
                <td>Alice Johnson</td>
                <td>Handmade Scarf</td>
                <td>₹450</td>
                <td><span class="status pending">Pending</span></td>
                <td><button class="btn-small">View</button></td>
              </tr>
              <tr>
                <td>#HC002</td>
                <td>Bob Smith</td>
                <td>Ceramic Mug</td>
                <td>₹320</td>
                <td><span class="status shipped">Shipped</span></td>
                <td><button class="btn-small">Track</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Product Listings -->
      <section class="product-listings">
        <div class="section-header">
          <h2>My Products</h2>
        </div>
        <div class="listings-grid">
          <div class="listing-card">
            <img src="https://via.placeholder.com/200x150" alt="Product">
            <div class="listing-info">
              <h3>Handmade Scarf</h3>
              <p>₹450</p>
              <div class="listing-actions">
                <button class="btn-small" onclick="openEditProductModal('Handmade Scarf', 'clothing', '450', '15', 'Beautiful handmade scarf made from premium wool', 'Wool', '200', 'handmade, wool, scarf', 'CLOHAN1234')">Edit</button>
                <button class="btn-small">Delete</button>
              </div>
            </div>
          </div>
          <div class="listing-card">
            <img src="https://via.placeholder.com/200x150" alt="Product">
            <div class="listing-info">
              <h3>Ceramic Mug</h3>
              <p>₹320</p>
              <div class="listing-actions">
                <button class="btn-small" onclick="openEditProductModal('Ceramic Mug', 'home', '320', '8', 'Handcrafted ceramic mug with unique design', 'Ceramic', '350', 'ceramic, handmade, mug', 'HOMCER5678')">Edit</button>
                <button class="btn-small">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script>
    // Flash alert close functionality
    document.addEventListener('DOMContentLoaded', function() {
      const alertClose = document.querySelector('.alert-close');
      const flashAlert = document.querySelector('.flash-alert');
      
      if (alertClose && flashAlert) {
        alertClose.addEventListener('click', function() {
          flashAlert.style.animation = 'slideOutUp 0.3s ease-out forwards';
          setTimeout(() => {
            flashAlert.remove();
          }, 300);
        });
      }
    });
      
      // Set up tips rotation
      setInterval(rotateTips, 10000); // Rotate tips every 10 seconds
      
      // Set up tip close button
      const tipCloseBtn = document.querySelector('.tip-close');
      if (tipCloseBtn) {
        tipCloseBtn.addEventListener('click', closeTipsBanner);
      }
    
    // Popup Menu Functions
    function toggleMenu() {
      const popupMenu = document.getElementById('popupMenu');
      const menuOverlay = document.getElementById('menuOverlay');
      
      if (popupMenu.classList.contains('active')) {
        closeMenu();
      } else {
        openMenu();
      }
    }
    
    function openMenu() {
      const popupMenu = document.getElementById('popupMenu');
      const menuOverlay = document.getElementById('menuOverlay');
      
      popupMenu.classList.add('active');
      menuOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
      const popupMenu = document.getElementById('popupMenu');
      const menuOverlay = document.getElementById('menuOverlay');
      
      popupMenu.classList.remove('active');
      menuOverlay.classList.remove('active');
      document.body.style.overflow = 'auto';
    }
    
    // Close menu when clicking on menu items
    document.addEventListener('DOMContentLoaded', function() {
      const menuItems = document.querySelectorAll('.menu-items a');
      menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          closeMenu();
        });
      });
    });
    
    // Add Product Modal Functions
    function openAddProductModal() {
      const modal = document.getElementById('addProductModal');
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    
    function closeAddProductModal() {
      const modal = document.getElementById('addProductModal');
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
      // Reset form
      document.getElementById('addProductForm').reset();
    }
    
                // SKU Generation Function
            function generateSKU(category, productName) {
              const categoryCode = getCategoryCode(category);
              const nameCode = productName.substring(0, 2).toUpperCase().replace(/[^A-Z]/g, 'X');
              
              // Generate 1 more alphabet to make total 4 alphabets
              const extraAlphabet = String.fromCharCode(65 + Math.floor(Math.random() * 26));
              
              // Generate 4-digit number (1 to 9999)
              const numberPart = Math.floor(Math.random() * 9999) + 1; // 1 to 9999
              
              // Create SKU with exactly 4 alphabets + 4 numbers (total 8 characters)
              const alphabetPart = `${categoryCode}${nameCode}${extraAlphabet}`; // 4 characters total
              
              return `${alphabetPart}${numberPart.toString().padStart(4, '0')}`;
            }
    
    function getCategoryCode(category) {
      const categoryCodes = {
        'clothing': 'CLO',
        'jewelry': 'JEW',
        'home': 'HOM',
        'art': 'ART',
        'beauty': 'BEA',
        'toys': 'TOY',
        'other': 'OTH'
      };
      return categoryCodes[category] || 'OTH';
    }
    
    // Handle form submission
    document.addEventListener('DOMContentLoaded', function() {
      const addProductForm = document.getElementById('addProductForm');
      
      addProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Generate SKU before form submission
        const productName = document.getElementById('productName').value;
        const productCategory = document.getElementById('productCategory').value;
        const generatedSKU = generateSKU(productCategory, productName);
        
        // Set the generated SKU
        document.getElementById('productSKU').value = generatedSKU;
        
        // Get form data
        const formData = new FormData(addProductForm);
        const productData = Object.fromEntries(formData);
        
        // Show success message with SKU
        showNotification(`Product added successfully! SKU: ${generatedSKU}`, 'success');
        
        // Close modal
        closeAddProductModal();
        
        // Here you would typically send the data to your backend
        console.log('Product Data:', productData);
      });
      
      // Close modal when clicking outside
      const modal = document.getElementById('addProductModal');
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          closeAddProductModal();
        }
      });
    });
    
    // Notification function
    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
      `;
      
      document.body.appendChild(notification);
      
      // Show notification
      setTimeout(() => {
        notification.classList.add('show');
      }, 100);
      
      // Remove notification after 3 seconds
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 3000);
    }
    
    // Edit Product Modal Functions
    function openEditProductModal(name, category, price, stock, description, material, weight, tags, sku) {
      const modal = document.getElementById('editProductModal');
      
      // Populate form fields with existing data
      document.getElementById('editProductName').value = name;
      document.getElementById('editProductCategory').value = category;
      document.getElementById('editProductPrice').value = price;
      document.getElementById('editProductStock').value = stock;
      document.getElementById('editProductDescription').value = description;
      document.getElementById('editProductMaterial').value = material;
      document.getElementById('editProductWeight').value = weight;
      document.getElementById('editProductTags').value = tags;
      document.getElementById('editProductSKU').value = sku;
      
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    
    function closeEditProductModal() {
      const modal = document.getElementById('editProductModal');
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
      // Reset form
      document.getElementById('editProductForm').reset();
    }
    
    // Handle edit form submission
    document.addEventListener('DOMContentLoaded', function() {
      const editProductForm = document.getElementById('editProductForm');
      
      editProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(editProductForm);
        const productData = Object.fromEntries(formData);
        
        // Show success message with existing SKU
        const existingSKU = document.getElementById('editProductSKU').value;
        showNotification(`Product updated successfully! SKU: ${existingSKU}`, 'success');
        
        // Close modal
        closeEditProductModal();
        
        // Here you would typically send the data to your backend
        console.log('Updated Product Data:', productData);
      });
      
      // Close edit modal when clicking outside
      const editModal = document.getElementById('editProductModal');
      editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
          closeEditProductModal();
        }
      });
    });
  </script>
</body>
</html>