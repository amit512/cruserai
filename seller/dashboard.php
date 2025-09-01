<?php
require_once __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); echo "<p>Forbidden.</p>"; require __DIR__ . '/../includes/footer.php'; exit;
}
$mine = Product::bySeller((int) $_SESSION['user']['id']);
?>
<div class="flex items-center justify-between mb-4">
  <h2 class="text-xl font-semibold">Seller Dashboard</h2>
  <a href="./product-new.php" class="rounded bg-black px-4 py-2 text-white">Add product</a>
</div>
<div class="rounded-xl bg-white shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="text-left p-3">ID</th>
        <th class="text-left p-3">Title</th>
        <th class="text-left p-3">Price</th>
        <th class="text-left p-3">Stock</th>
        <th class="text-left p-3">Active</th>
        <th class="text-left p-3">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mine as $p): ?>
      <tr class="border-t">
        <td class="p-3"><?php echo (int)$p['id']; ?></td>
        <td class="p-3"><?php echo htmlspecialchars($p['title']); ?></td>
        <td class="p-3">Rs. <?php echo number_format((float)$p['price'], 2); ?></td>
        <td class="p-3"><?php echo (int)$p['stock']; ?></td>
        <td class="p-3"><?php echo $p['is_active'] ? 'Yes' : 'No'; ?></td>
        <td class="p-3 space-x-2">
          <a class="underline" href="./product-edit.php?id=<?php echo (int)$p['id']; ?>">Edit</a>
          <form action="../actions/product_delete.php" method="post" class="inline" onsubmit="return confirm('Delete this product?')">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
            <button type="submit" class="underline text-red-600">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
