<?php
require_once __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); echo "<p>Forbidden.</p>"; require __DIR__ . '/../includes/footer.php'; exit;
}
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $id ? Product::find($id) : null;
if (!$product || $product['seller_id'] != $_SESSION['user']['id']) {
    http_response_code(404); echo "<p>Product not found.</p>"; require __DIR__ . '/../includes/footer.php'; exit;
}
?>
<h2 class="text-xl font-semibold mb-4">Edit Product</h2>
<form action="../actions/product_update.php" method="post" enctype="multipart/form-data" class="space-y-4 max-w-xl">
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
  <label class="block">
    <span class="text-sm">Title</span>
    <input class="mt-1 w-full rounded border p-2" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
  </label>
  <label class="block">
    <span class="text-sm">Description</span>
    <textarea class="mt-1 w-full rounded border p-2" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
  </label>
  <div class="grid grid-cols-3 gap-4">
    <label class="block col-span-1">
      <span class="text-sm">Price (Rs.)</span>
      <input class="mt-1 w-full rounded border p-2" type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
    </label>
    <label class="block col-span-1">
      <span class="text-sm">Stock</span>
      <input class="mt-1 w-full rounded border p-2" type="number" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
    </label>
    <label class="flex items-center space-x-2 mt-6">
      <input type="checkbox" name="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
      <span>Active</span>
    </label>
  </div>
  <label class="block">
    <span class="text-sm">Replace Image</span>
    <input class="mt-1 w-full" type="file" name="image">
  </label>
  <button class="rounded bg-black px-4 py-2 text-white" type="submit">Update</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
