<?php


session_start();
// var_dump($_SESSION['id']);
// exit();
// $_SESSION['role_id'] = 2;
// if (!isset($_SESSION['id'])) {
//     header("Location: ../login.php");
//     exit;
// }
$type = "Home";
require '../requires/connect.php';
require '../templates/template_nav.php';

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
        au.role_id AS answerRole,
        m.name AS moduleName
    FROM question q
    JOIN users u ON q.userId = u.id
    LEFT JOIN answer a ON a.questionId = q.id
    LEFT JOIN users au ON a.userId = au.id
    LEFT JOIN module m ON q.moduleId = m.id
    WHERE q.is_approve = 1
    ORDER BY q.likeCount DESC
    LIMIT 3
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

?>

<!-- hero section -->
<section class="flex flex-col md:flex-row items-center justify-center gap-12 md:gap-8 px-6 py-16 md:p-24 relative">

    <!-- Text Content -->
    <div class=" max-w-md h-[50vh] md:h-auto flex justify-center items-center overflow-hidden md:bg-none">

        <div class="absolute inset-0 bg-[url('../img/test1.png')] bg-cover bg-center blur-sm md:hidden w-full"></div>

        <div class="relative z-10 space-y-4 text-center md:text-left px-5 py-6 bg-white/50 md:bg-transparent shadow rounded">
            <h1 class="text-3xl md:text-4xl font-bold">Unlock Your Learning Potential</h1>
            <small class="text-gray-700">Interactive courses and collaborative study materials await you!</small>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start mt-3">
                <a href="about_us.php" class="bg-gray-900 text-white px-5 py-2 rounded-md hover:bg-gray-800 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700">
                    Learn More
                </a>
                <a href="<?= isset($_SESSION['id']) ? 'learning_path.php' : 'login.php' ?>" class="bg-purple-600 text-white px-5 py-2 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
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
    <p class="text-gray-600 mt-2 mb-4">Explore a variety of subjects and courses tailored to your needs.</p>
    <a href="courses.php" class=" bg-purple-600 text-white px-4 py-2 mt-4 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500  ">Browse Now</a>

    <div class="grid md:grid-cols-3 gap-6 px-4 max-w-6xl mt-5 mx-auto">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <a href="enroll.php?id=<?= htmlspecialchars($row['id']) ?>" class="block bg-white p-4 border rounded shadow-sm hover:shadow-md transition">
                    <img src="../uploads/thumbnails/<?= htmlspecialchars($row['thumbnail']) ?>" class="w-full h-48 object-cover rounded" alt="Course Thumbnail" />
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
<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Hot Topics</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Dive into trending conversations with expert responses
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php while ($row = $queResult->fetch_assoc()): ?>
                <article class="relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 group h-full flex flex-col">
                    <div class="absolute inset-0 bg-gradient-to-br from-white to-gray-50 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    <div class="p-6 relative z-10 flex-grow flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                <?= htmlspecialchars($row['moduleName']) ?>
                            </span>
                            <div class="flex items-center">
                                <span class="h-2 w-2 rounded-full bg-green-400 mr-1.5"></span>
                                <span class="text-xs text-gray-500">Answered</span>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-3 leading-snug">
                            <a href="question_detail.php?id=<?= $row['questionId'] ?>" class="hover:text-purple-600 transition-colors">
                                <?= htmlspecialchars(mb_substr($row['title'], 0, 30)) . (mb_strlen($row['title']) > 30 ? '...' : '') ?>
                            </a>
                        </h3>

                        <p class="text-gray-600 mb-5 line-clamp-2 flex-grow">
                            <?= htmlspecialchars(mb_substr($row['question'], 0, 30)) . (mb_strlen($row['question']) > 30 ? '...' : '') ?>
                        </p>

                        <?php if ($row['answer']): ?>
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($row['answerUserName']) ?>
                                    <?php if ($row['answerRole'] == 1): ?>
                                        (Admin)
                                    <?php elseif ($row['answerRole'] == 2): ?>
                                        (Teacher)
                                    <?php endif; ?>
                                </span>
                                <p class="text-gray-700 text-sm line-clamp-2">
                                    <?= htmlspecialchars($row['answer']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-700">Asked by <?= htmlspecialchars($row['userName']) ?></span>
                            </div>
                            <a href="question_detail.php?id=<?= $row['questionId'] ?>" class="text-sm font-medium flex items-center text-purple-600 hover:text-purple-800 transition-colors">
                                View discussion
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <div class="mt-16 text-center">
            <a href="discussion.php" class="inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-white bg-indigo-700 hover:bg-indigo-600 rounded-lg transition-all transform hover:-translate-y-1 focus:ring-4 focus:ring-indigo-300 focus:ring-offset-2 shadow-lg">
                Explore All Topics
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 -mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
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
                <a href="<?= htmlspecialchars($row['slug']) ?>" class="block bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
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
        <a href="blogs.php" class="bg-purple-600 text-white px-4 py-3 mt-4 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">View All Articles</a>
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
                        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg text-left max-w-3xl mx-auto">
                            <p class="text-lg font-semibold"><?= htmlspecialchars($testimonial['name']) ?></p>
                            <p class="text-gray-600 mt-3 text-sm sm:text-base truncate-text" data-full-text="<?= htmlspecialchars($testimonial['text']) ?>">
                                <?= htmlspecialchars($testimonial['text']) ?>
                            </p>
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
<?php if (!isset($_SESSION['id'])): ?>
    <!-- Not logged in: Show Sign Up section -->
    <section class="py-10 px-4 bg-gray-200 text-center">
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
    <section class="py-10 px-4 bg-gray-200 text-center">
        <h2 class="text-xl font-semibold">Need Help?</h2>
        <p class="text-sm text-gray-600 mb-4">Contact our support team for assistance or ask your questions.</p>
        <a href="tel:+959681652929" class="bg-purple-600 text-white px-5 py-2 rounded-md hover:bg-purple-700 hover:scale-105 transition-all duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">Contact Us</a>

        <div class="mt-6 max-w-xl mx-auto text-sm text-gray-700">
            <div class="bg-white border p-4 rounded">
                <p>We're here to help you succeed. Reach out anytime for support or guidance!</p>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php require '../templates/template_backtotop.php'  ?>




<?php require '../templates/template_footer.php'  ?>