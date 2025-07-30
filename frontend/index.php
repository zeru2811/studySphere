<?php
session_start(); 
// $_SESSION['role_id'] = 2;
// if (!isset($_SESSION['id'])) {
//     header("Location: ../login.php");
//     exit;
// }
$type = "Home";
require '../templates/template_nav.php';
require '../requires/connect.php';

$roleId = isset($_SESSION['role_id']) ? intval($_SESSION['role_id']) : 0;




$blogsql = "SELECT * FROM blog ORDER BY created_at DESC LIMIT 3";
$blogresult = $mysqli->query($blogsql);

$sql = "SELECT courses.*, users.name AS teacher_name 
FROM courses 
JOIN users ON courses.teacherId = users.id 
ORDER BY courses.created_at 
LIMIT 3";

$result = $mysqli->query($sql);

$Quesql = "
    SELECT 
        q.id AS questionId,
        q.title,
        q.question,
        q.likeCount,
        u.name AS userName,
        a.answer,
        au.name AS answerUserName,
        m.name AS moduleName
    FROM question q
    JOIN users u ON q.userId = u.id
    LEFT JOIN answer a ON a.questionId = q.id
    LEFT JOIN users au ON a.userId = au.id
    LEFT JOIN module m ON q.moduleId = m.id
    WHERE q.is_approve = 1
    ORDER BY q.likeCount DESC
    LIMIT 2
";

$queResult = $mysqli->query($Quesql);


$Feedsql = "SELECT f.text, u.name 
        FROM feedback f 
        JOIN users u ON f.userId = u.id 
        ORDER BY f.created_at DESC 
        LIMIT 3";
$Feedresult = $mysqli->query($Feedsql);
$testimonials = [];
if ($Feedresult && $Feedresult->num_rows > 0) {
    while ($row = $Feedresult->fetch_assoc()) {
        $testimonials[] = $row;
    }
}

// $_SESSION['user_id'] = 1;
?>

<!-- hero section -->
<section class="flex flex-col md:flex-row items-center justify-center gap-12 md:gap-8 px-6 py-16 md:p-24 relative">

    <!-- Text Content -->
    <div class=" max-w-md h-[50vh] md:h-auto flex justify-center items-center overflow-hidden md:bg-none">

        <div class="absolute inset-0 bg-[url('../img/test1.png')] bg-cover bg-center blur-sm md:hidden w-full"></div>

        <div class="relative z-10 space-y-4 text-center md:text-left px-14 py-6 bg-white/50 md:bg-transparent shadow rounded">
            <h1 class="text-3xl md:text-4xl font-bold">Unlock Your Learning Potential</h1>
            <small class="text-gray-700">Interactive courses and collaborative study materials await you!</small>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start mt-3">
                <a href="about_us.php" class="bg-gray-900 text-white px-5 py-2 rounded-md hover:bg-gray-800 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700">
                    Learn More
                </a>
                <a href="<?= isset($_SESSION['user_id']) ? 'learning_path.php' : 'login.php' ?>" class="bg-purple-600 text-white px-5 py-2 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    Get Started
                </a>
            </div>
        </div>

    </div>


    <!-- Responsive Image -->
    <img
        src="../assets/img/hero.png"
        alt="Hero Image"
        class="w-full hidden md:block h-[50vh] md:h-auto max-w-[250px] md:max-w-[350px] lg:max-w-[600px] rounded mt-10 md:mt-0 shadow-sm"
        loading="lazy" />

</section>

<!-- course section -->
<section class="text-center py-10 bg-gray-50">
    <h2 class="text-2xl font-semibold">Courses Offered</h2>
    <p class="text-gray-600 mt-2">Explore a variety of subjects and courses tailored to your needs.</p>
    <button class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 mt-4">Browse Now</button>

    <div class="grid md:grid-cols-3 gap-6 px-4 max-w-6xl mt-5 mx-auto">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <a href="#" class="block bg-white p-4 border rounded shadow-sm hover:shadow-md transition">
                    <img src="<?= htmlspecialchars($row['thumbnail']) ?>" class="w-full h-48 object-cover rounded" alt="Course Thumbnail" />
                    <h3 class="font-semibold mt-2 text-lg"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($row['teacher_name']) ?></p>
                    <div class="text-purple-600 font-bold mt-1">Ks <?= number_format($row['price']) ?></div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 col-span-3">No courses available right now.</p>
        <?php endif; ?>
    </div>
</section>

<!-- discussion section -->
<section class="py-12 px-4 bg-gray-50">
    <h2 class="text-2xl font-semibold text-center mb-4">Top IT Discussions</h2>
    <p class="text-gray-600 text-center mb-6">
        Explore the most reacted questions in IT, answered by our teachers.
    </p>

    <div class="max-w-4xl mx-auto space-y-8">
        <?php while ($row = $queResult->fetch_assoc()): ?>
            <a href="/discussion/<?= $row['questionId'] ?>" class="block hover:shadow-lg transition-shadow bg-white p-6 border rounded shadow-sm">
                <div class="flex justify-between items-center mb-2">
                    <p class="font-semibold text-indigo-700">@<?= htmlspecialchars($row['userName']) ?></p>
                    <span class="text-xs px-2 py-0.5 bg-green-200 text-green-800 rounded">Approved</span>
                </div>
                <div class="mb-2">
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded">#<?= htmlspecialchars($row['moduleName']) ?></span>
                </div>
                <p class="text-lg font-medium mb-1"><?= htmlspecialchars($row['title']) ?></p>
                <p class="text-gray-700 mb-3 truncate"><?= htmlspecialchars($row['question']) ?></p>

                <?php if ($row['answer']): ?>
                <div class="bg-indigo-50 p-4 rounded border-l-4 border-indigo-400 mb-4">
                    <p class="font-semibold text-indigo-700 mb-1">Teacher @<?= htmlspecialchars($row['answerUserName']) ?> answered:</p>
                    <p class="text-gray-700 text-sm"><?= htmlspecialchars($row['answer']) ?></p>
                </div>
                <?php endif; ?>

                <div class="flex space-x-6 text-gray-600 text-sm">
                    <div class="flex items-center space-x-1">
                        👍 <span><?= $row['likeCount'] ?></span>
                    </div>
                    <div class="flex items-center space-x-1">
                        👎 <span>0</span>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="text-center mt-8">
        <a href="/discussions.html"
           class="bg-purple-600 text-white px-5 py-3 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
            View All Discussions
        </a>
    </div>
</section>

<!-- blog section -->
<section class="py-12 px-4 border-t border-b bg-gray-50">
    <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-800">From the Blog</h2>
        <p class="mt-2 text-gray-600">Latest insights, tutorials, and education trends.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mt-10 max-w-6xl mx-auto">
        <?php if ($blogresult && $blogresult->num_rows > 0): ?>
            <?php while ($row = $blogresult->fetch_assoc()): ?>
                <a href="#" class="block bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    <img src="<?= htmlspecialchars($row['thumbnail']) ?>" alt="Blog Image" class="w-full h-48 object-cover" />
                    <div class="p-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 100, '...')) ?></p>
                        <span class="text-xs text-purple-600 font-medium mt-2 inline-block">Read more →</span>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center col-span-3 text-gray-500">No blog posts available.</p>
        <?php endif; ?>
    </div>


    <div class="mt-8 text-center">
        <a href="#" class="bg-purple-600 text-white px-4 py-3 mt-4 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">View All Articles</a>
    </div>
</section>

<!-- feedback section -->
<section class="py-16 px-4 sm:px-6 bg-gray-50 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold mb-3">What Students Are Saying</h2>
    <p class="text-gray-600 mb-10 text-base sm:text-lg">Hear from fellow students about their experiences.</p>

    <div class="relative max-w-full mx-auto">
        <div class="overflow-hidden">
            <div id="testimonialWrapper" class="flex transition-transform duration-700 ease-in-out">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="min-w-full px-4">
                    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-md text-left max-w-3xl mx-auto">
                        <p class="text-lg font-semibold"><?= htmlspecialchars($testimonial['name']) ?></p>
                        <p class="text-gray-600 mt-3 text-sm sm:text-base truncate-text" data-full-text="<?= htmlspecialchars($testimonial['text']) ?>">
                            <?= htmlspecialchars($testimonial['text']) ?>
                        </p>
                        <a href="testimonials.html" class="text-purple-500 text-sm mt-2 inline-block hover:underline">Read More</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-center gap-3 mt-6">
            <?php for ($i = 0; $i < count($testimonials); $i++): ?>
            <button onclick="goToSlide(<?= $i ?>)" class="w-3 h-3 rounded-full bg-gray-400 hover:bg-gray-600" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endfor; ?>
        </div>
    </div>


    <script>
        const wrapper = document.getElementById('testimonialWrapper');
        let currentIndex = 0;
        const totalSlides = wrapper.children.length;

        function goToSlide(index) {
            currentIndex = index;
            wrapper.style.transform = `translateX(-${index * 100}%)`;
        }

        function limitWords(text, wordLimit = 30) {
            const words = text.trim().split(/\s+/);
            return words.length > wordLimit ?
                words.slice(0, wordLimit).join(" ") + "..." :
                text;
        }

        // Truncate testimonials on load
        document.querySelectorAll('.truncate-text').forEach(el => {
            const fullText = el.getAttribute('data-full-text') || '';
            el.innerText = limitWords(fullText, 30);
        });

        setInterval(() => {
            currentIndex = (currentIndex + 1) % totalSlides;
            goToSlide(currentIndex);
        }, 5000);
    </script>
</section>

<!-- Call to Action Section-->
<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Not logged in: Show Sign Up section -->
    <section class="py-10 px-4 bg-gray-100 text-center">
        <h2 class="text-xl font-semibold">Don't have an account?</h2>
        <p class="text-sm text-gray-600 mb-4">Create a new account to enjoy all our features.</p>
        <a href="register.php" class="bg-gray-900 text-white px-5 py-2 rounded-md hover:bg-gray-800 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700">Sign Up Now</a>

        <div class="mt-6 max-w-xl mx-auto text-sm text-gray-700">
            <div class="bg-white border p-4 rounded">
                <p>Join us today! Sign up to get started. Start enjoying the benefits right away.</p>
            </div>
        </div>
    </section>
<?php else: ?>
    <!-- Logged in: Show Contact or Help section -->
    <section class="py-10 px-4 bg-gray-100 text-center">
        <h2 class="text-xl font-semibold">Need Help?</h2>
        <p class="text-sm text-gray-600 mb-4">Contact our support team for assistance or ask your questions.</p>
        <a href="contact.php" class="bg-purple-600 text-white px-5 py-2 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">Contact Us</a>

        <div class="mt-6 max-w-xl mx-auto text-sm text-gray-700">
            <div class="bg-white border p-4 rounded">
                <p>We're here to help you succeed. Reach out anytime for support or guidance!</p>
            </div>
        </div>
    </section>
<?php endif; ?>





<?php require '../templates/template_footer.php'  ?>
