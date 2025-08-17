<?php 
session_start(); 
$type = "Blog";
require '../requires/connect.php';
require '../templates/template_nav.php';
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}
$slug = $_GET['slug'] ?? '';
$stmt = $mysqli->prepare("SELECT * FROM blog WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

if (!$blog) {
    echo "Blog not found!";
    exit;
}

?>

<main class="max-w-4xl mx-auto px-4 py-12">
  <img src="<?= htmlspecialchars($blog['thumbnail']) ?>" class="w-full h-64 object-cover rounded-xl mb-6" alt="<?= htmlspecialchars($blog['title']) ?>">
  <h1 class="text-3xl font-bold text-blue-700 mb-4"><?= htmlspecialchars($blog['title']) ?></h1>
  <div class="text-sm text-gray-500 mb-6">
    <?= date("F j, Y", strtotime($blog['created_at'])) ?> • <?= ucfirst($blog['blogCatagory']) ?>
  </div>
  <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($blog['description'])) ?></p>
</main>

    <?php require '../templates/template_backtotop.php'  ?>
<?php require '../templates/template_footer.php'  ?>