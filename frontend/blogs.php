<?php 
session_start(); 
$type = "Blogs";
require '../requires/connect.php';
require '../templates/template_nav.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return !empty($text) ? $text : 'n-a';
}
$title = $_POST['title'] ?? '';
$slug = slugify($title);

$sql = "SELECT * FROM blog ORDER BY created_at DESC";
$result = $mysqli->query($sql);

?>

<!-- hero section -->
<section class="bg-blue-50 py-16 text-center px-4">
    <h2 class="text-4xl font-extrabold text-blue-700">Latest Articles & Learning Tips</h2>
    <p class="mt-3 text-lg text-gray-600 max-w-2xl mx-auto">Explore practical advice, study techniques, and stories from students and educators in the StudySphere community.</p>
</section>


<main class="max-w-7xl mx-auto px-4 py-12">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
    <?php while($row = $result->fetch_assoc()): ?>
      <a href="<?= htmlspecialchars($row['slug']) ?>" class="bg-white rounded-xl shadow-md hover:shadow-lg transition duration-300 group overflow-hidden">
        <img src="<?= htmlspecialchars($row['thumbnail']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>
        <div class="p-6">
          <h3 class="text-xl font-semibold text-blue-700 group-hover:underline"><?= htmlspecialchars($row['title']) ?></h3>
          <p class="mt-2 text-gray-600 text-sm"><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</p>
          <div class="mt-4 text-xs text-gray-400"><?= date("F j, Y", strtotime($row['created_at'])) ?> • <?= ucfirst($row['blogCatagory']) ?></div>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
</main>

<?php require '../templates/template_backtotop.php'  ?>
<?php require '../templates/template_footer.php'  ?>


