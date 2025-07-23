<a href="enroll.php?id=<?= $course['id'] ?>" class="course-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <div class="relative">
        <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['name']) ?>" class="w-full h-48 object-cover">
        <?php if (in_array($course['id'], $newCourseIds)): ?>
            <div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">NEW</div>
        <?php elseif (!empty($ratings[$course['id']]) && $ratings[$course['id']] >= 5): ?>
            <div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded">POPULAR</div>
        <?php endif; ?>
    </div>
    <div class="p-6">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm font-medium text-indigo-600"><?= strtoupper($course['catName']) ?></span>
            <span class="text-sm text-gray-500"><?= rand(15, 45) ?> hours</span>
        </div>
        <h3 class="course-title text-xl font-bold text-gray-900 mb-3 transition-colors duration-300"><?= htmlspecialchars($course['name']) ?></h3>
        <p class="text-gray-600 mb-4"><?= htmlspecialchars($course['title']) ?></p>
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="flex -space-x-1">
                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/44.jpg" alt="">
                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/men/32.jpg" alt="">
                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/68.jpg" alt="">
                </div>
                <span class="ml-2 text-sm text-gray-500">+<?= rand(50, 150) ?> enrolled</span>
            </div>
            <span class="text-lg font-bold text-gray-900">
                Ks<?= $course['price'] ?>
            </span>
        </div>
    </div>
</a>
