<?php
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
require "../requires/common.php";
require "../requires/connect.php";
require "../requires/common_function.php";

$pagetitle = "Blog Management";

// Handle Blog Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_blog'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $blogCategory = $_POST['blogCatagory'];
    $slug = trim($_POST['slug']);
    $authorName = trim($_POST['authorName']);
    $thumbnail = '';
    
    // Basic validation
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description cannot be empty";
    } else {
        // Thumbnail upload handling
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == UPLOAD_ERR_OK) {
            $targetDir = "../uploads/blog/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = basename($_FILES['thumbnail']['name']);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Check if file is an image
            $check = getimagesize($_FILES['thumbnail']['tmp_name']);
            if ($check !== false && in_array($fileType, $allowedTypes)) {
                // Generate unique filename
                $newFileName = uniqid() . '.' . $fileType;
                $targetFilePath = $targetDir . $newFileName;
                
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFilePath)) {
                    $thumbnail = $targetFilePath;
                } else {
                    $_SESSION['error'] = "Failed to upload thumbnail image.";
                }
            } else {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        if (!isset($_SESSION['error'])) {
            // Prepare and execute the insert query with prepared statement
            $stmt = $mysqli->prepare("INSERT INTO blog (title, description, blogCatagory, thumbnail, slug, authorName, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ssssss", $title, $description, $blogCategory, $thumbnail, $slug, $authorName);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Blog post created successfully!";
                } else {
                    $_SESSION['error'] = "Failed to create blog post: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Database error: " . $mysqli->error;
            }
        }
    }
    header('Location: blog.php');
    exit();
}

// Handle Blog Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_blog'])) {
    $id = intval($_POST['blog_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $blogCategory = $_POST['blogCatagory'];
    $slug = trim($_POST['slug']);
    $authorName = trim($_POST['authorName']);
    $thumbnail = trim($_POST['existing_thumbnail']);
    
    // Handle new thumbnail upload if provided
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../uploads/blog/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = basename($_FILES['thumbnail']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Check if file is an image
        $check = getimagesize($_FILES['thumbnail']['tmp_name']);
        if ($check !== false && in_array($fileType, $allowedTypes)) {
            // Generate unique filename
            $newFileName = uniqid() . '.' . $fileType;
            $targetFilePath = $targetDir . $newFileName;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFilePath)) {
                // Delete old thumbnail if it exists
                if (!empty($thumbnail) && file_exists($thumbnail)) {
                    unlink($thumbnail);
                }
                $thumbnail = $targetFilePath;
            } else {
                $_SESSION['error'] = "Failed to upload thumbnail image.";
            }
        } else {
            $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description cannot be empty";
    } else {
        $stmt = $mysqli->prepare("UPDATE blog SET title = ?, description = ?, blogCatagory = ?, thumbnail = ?, slug = ?, authorName = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("ssssssi", $title, $description, $blogCategory, $thumbnail, $slug, $authorName, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Blog post updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update blog post: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header('Location: blog.php');
    exit();
}

// Handle Blog Deletion
if (isset($_GET['delete_blog'])) {
    $id = intval($_GET['delete_blog']);
    
    // First get the thumbnail path to delete the file
    $stmt = $mysqli->prepare("SELECT thumbnail FROM blog WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($thumbnail);
        $stmt->fetch();
        $stmt->close();
        
        // Delete the blog post
        $stmt = $mysqli->prepare("DELETE FROM blog WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // Delete the thumbnail file if it exists
                if (!empty($thumbnail) && file_exists($thumbnail)) {
                    unlink($thumbnail);
                }
                $_SESSION['message'] = "Blog post deleted successfully.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: blog.php");
    exit();
}

// Get all blog posts
$blogs = [];
$blogsQuery = $mysqli->prepare("SELECT * FROM blog ORDER BY created_at DESC");

if ($blogsQuery && $blogsQuery->execute()) {
    $blogs = $blogsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $_SESSION['error'] = "Failed to load blog posts: " . $mysqli->error;
}

require "./templates/admin_header.php";
require "./templates/admin_sidebar.php";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Blog Management</h1>
            <p class="text-gray-600 mt-2">Create and manage blog posts</p>
        </div>
        <button onclick="openModal('createBlogModal')"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Blog
        </button>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <p><?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                <p><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Blog Posts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($blogs)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-newspaper text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg">No blog posts found</p>
                <p class="text-sm mt-1">Create your first blog post to get started</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($blogs as $blog): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?= $blog['blogCatagory'] === 'tech' ? 'bg-blue-100 text-blue-800' : 
                                           ($blog['blogCatagory'] === 'education' ? 'bg-green-100 text-green-800' : 
                                           ($blog['blogCatagory'] === 'announcement' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) ?>">
                                        <?= ucfirst($blog['blogCatagory']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($blog['authorName'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($blog['created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditModal(<?= $blog['id'] ?>, 
                                        '<?= htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8') ?>', 
                                        '<?= htmlspecialchars($blog['description'], ENT_QUOTES, 'UTF-8') ?>',
                                        '<?= $blog['blogCatagory'] ?>',
                                        '<?= htmlspecialchars($blog['thumbnail'], ENT_QUOTES, 'UTF-8') ?>',
                                        '<?= htmlspecialchars($blog['slug'], ENT_QUOTES, 'UTF-8') ?>',
                                        '<?= htmlspecialchars($blog['authorName'], ENT_QUOTES, 'UTF-8') ?>')"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button onclick="confirmDeleteBlog(<?= $blog['id'] ?>)"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Blog Modal -->
<div id="createBlogModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Create New Blog Post</h2>
                <button onclick="closeModal('createBlogModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="blogTitle" class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                        <input type="text" name="title" id="blogTitle" 
                               class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                               placeholder="Blog post title" required>
                    </div>
                    <div>
                        <label for="blogCategory" class="block text-sm font-medium text-gray-700 mb-1">Category*</label>
                        <select name="blogCatagory" id="blogCategory" 
                                class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            <option value="general">General</option>
                            <option value="announcement">Announcement</option>
                            <option value="tech">Tech</option>
                            <option value="education">Education</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="blogDescription" class="block text-sm font-medium text-gray-700 mb-1">Description*</label>
                    <textarea name="description" id="blogDescription" rows="5"
                              class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                              placeholder="Blog content" required></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="blogThumbnail" class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                        <input type="file" name="thumbnail" id="blogThumbnail"
                            class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Only JPG, JPEG, PNG & GIF files are allowed.</p>
                    </div>
                    <div>
                        <label for="blogSlug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" name="slug" id="blogSlug" 
                               class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                               placeholder="blog-post-title">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="blogAuthor" class="block text-sm font-medium text-gray-700 mb-1">Author Name</label>
                    <input type="text" name="authorName" id="blogAuthor" 
                           class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                           placeholder="Author name">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('createBlogModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="create_blog"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i> Create Blog Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Blog Modal -->
<div id="editBlogModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Edit Blog Post</h2>
                <button onclick="closeModal('editBlogModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="blog_id" id="editBlogId">
                <input type="hidden" name="existing_thumbnail" id="editExistingThumbnail">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="editBlogTitle" class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                        <input type="text" name="title" id="editBlogTitle" 
                               class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                               placeholder="Blog post title" required>
                    </div>
                    <div>
                        <label for="editBlogCategory" class="block text-sm font-medium text-gray-700 mb-1">Category*</label>
                        <select name="blogCatagory" id="editBlogCategory" 
                                class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            <option value="general">General</option>
                            <option value="announcement">Announcement</option>
                            <option value="tech">Tech</option>
                            <option value="education">Education</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="editBlogDescription" class="block text-sm font-medium text-gray-700 mb-1">Description*</label>
                    <textarea name="description" id="editBlogDescription" rows="5"
                              class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                              placeholder="Blog content" required></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="editBlogThumbnail" class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                        <input type="file" name="thumbnail" id="editBlogThumbnail"
                            class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep current thumbnail.</p>
                        <div id="currentThumbnailPreview" class="mt-2"></div>
                    </div>
                    <div>
                        <label for="editBlogSlug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" name="slug" id="editBlogSlug" 
                               class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                               placeholder="blog-post-title">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="editBlogAuthor" class="block text-sm font-medium text-gray-700 mb-1">Author Name</label>
                    <input type="text" name="authorName" id="editBlogAuthor" 
                           class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                           placeholder="Author name">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editBlogModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="update_blog"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Blog Modal -->
<div id="deleteBlogModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteBlogModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this blog post? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteBlogModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteBlogLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('div').classList.add('scale-100');
        modal.querySelector('div').classList.remove('scale-95');
    }, 10);
    document.body.classList.add('overflow-hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.querySelector('div').classList.remove('scale-100');
    modal.querySelector('div').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 150);
}

function openEditModal(id, title, description, category, thumbnail, slug, author) {
    document.getElementById('editBlogId').value = id;
    document.getElementById('editBlogTitle').value = title;
    document.getElementById('editBlogDescription').value = description;
    document.getElementById('editBlogCategory').value = category;
    document.getElementById('editExistingThumbnail').value = thumbnail;
    document.getElementById('editBlogSlug').value = slug;
    document.getElementById('editBlogAuthor').value = author;
    
    // Show current thumbnail preview if exists
    const previewContainer = document.getElementById('currentThumbnailPreview');
    previewContainer.innerHTML = '';
    if (thumbnail) {
        previewContainer.innerHTML = `
            <p class="text-xs text-gray-500 mb-1">Current Thumbnail:</p>
            <img src="${thumbnail}" alt="Current thumbnail" class="h-20 object-cover rounded">
        `;
    }
    
    openModal('editBlogModal');
}

function confirmDeleteBlog(id) {
    document.getElementById('deleteBlogLink').href = `blog.php?delete_blog=${id}`;
    openModal('deleteBlogModal');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
});

// Prevent propagation when clicking inside modal content
document.querySelectorAll('.modal-content').forEach(modal => {
    modal.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});
</script>

<?php require "./templates/admin_footer.php"; ?>