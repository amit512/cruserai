<?php
require_once __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); echo "<p>Forbidden.</p>"; require __DIR__ . '/../includes/footer.php'; exit;
}
?>
<h2 class="text-xl font-semibold mb-4">New Product</h2>
<form action="../actions/product_create.php" method="post" enctype="multipart/form-data" class="space-y-4 max-w-xl">
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
  <label class="block">
    <span class="text-sm">Title</span>
    <input class="mt-1 w-full rounded border p-2" name="title" required>
  </label>
  <label class="block">
    <span class="text-sm">Description</span>
    <textarea class="mt-1 w-full rounded border p-2" name="description" rows="4" required></textarea>
  </label>
  <div class="grid grid-cols-3 gap-4">
    <label class="block col-span-1">
      <span class="text-sm">Price (Rs.)</span>
      <input class="mt-1 w-full rounded border p-2" type="number" step="0.01" name="price" required>
    </label>
    <label class="block col-span-1">
      <span class="text-sm">Stock</span>
      <input class="mt-1 w-full rounded border p-2" type="number" name="stock" required>
    </label>
    <label class="flex items-center space-x-2 mt-6">
      <input type="checkbox" name="is_active" value="1" checked>
      <span>Active</span>
    </label>
  </div>
  <label class="block">
    <span class="text-sm">Image</span>
    <input class="mt-1 w-full" type="file" name="image">
  </label>
  <button class="rounded bg-black px-4 py-2 text-white" type="submit">Create</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
