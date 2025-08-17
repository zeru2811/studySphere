<?php 
date_default_timezone_set('Asia/Yangon');
$server_name = "localhost";
// $user_name = "root";
// $password = "";
$user_name = "zuzadpsz_sphere_user";
$password = "WI0kst3p=OEto~w]";

$mysqli = new mysqli($server_name, $user_name, $password);

if($mysqli->connect_errno){
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

create_database($mysqli);
function create_database($mysqli){
    // zuzadpsz_sphere
    $sql = "CREATE DATABASE IF NOT EXISTS `zuzadpsz_sphere` 
        DEFAULT CHARACTER SET utf8mb4 
        COLLATE utf8mb4_general_ci";
    if ($mysqli->query($sql)) {
        return true;
    }
    return false;
}

function select_db($mysqli)
{
    if ($mysqli->select_db("zuzadpsz_sphere")) {
        return true;
    }
    return false;
}

select_db($mysqli);
create_table($mysqli);

function create_table($mysqli){
    // role
    $sql = "CREATE TABLE IF NOT EXISTS role (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // // Insert default roles if they don't exist
    // $sql = "INSERT IGNORE INTO role (name) VALUES 
    //     ('Admin'),
    //     ('Teacher'),
    //     ('Student'),
    //     ('External User')";
    // if ($mysqli->query($sql) === false) return false;

    $sql = "CREATE TABLE IF NOT EXISTS category (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;
   
    // users
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(50),
        password VARCHAR(200),
        gender ENUM('male', 'female', 'other') DEFAULT NULL, 
        role_id INT,
        uniqueId VARCHAR(100) UNIQUE,
        profile_photo TEXT,
        status BOOLEAN DEFAULT TRUE,
        face_data TEXT,
        note VARCHAR(255) DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES role(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;
     
    // feedback
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        text TEXT,
        userId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;


    // baseClass
    $sql = "CREATE TABLE IF NOT EXISTS baseClass (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_complete BOOLEAN DEFAULT FALSE,
        userId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // password_token
    $sql = "CREATE TABLE IF NOT EXISTS password_token (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reset_code varchar(10),
        userId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // courses
    $sql = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        price INT,
        title VARCHAR(100),
        description TEXT,
        thumbnail TEXT,
        teacherId INT,
        categoryId INT,
        isCertificate BOOLEAN DEFAULT FALSE,
        totalHours INT,
        `function` TEXT,
        realProjectCount INT,
        is_free BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacherId) REFERENCES users(id),
        FOREIGN KEY (categoryId) REFERENCES category(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;


    // subject
    $sql = "CREATE TABLE IF NOT EXISTS subject (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // course_subject
    $sql = "CREATE TABLE IF NOT EXISTS course_subject (
        id INT AUTO_INCREMENT PRIMARY KEY,
        courseId INT,
        subjectId INT,
        display_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (courseId) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (subjectId) REFERENCES subject(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;


    // lessons
    $sql = "CREATE TABLE IF NOT EXISTS lessons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lessonUrl TEXT,
        title VARCHAR(200),
        course_subject_id INT,
        description TEXT,
        duration VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_subject_id) REFERENCES course_subject(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;


    $sql = "CREATE TABLE IF NOT EXISTS lesson_completions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lesson_id INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_lesson (user_id, lesson_id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // comment
    $sql = "CREATE TABLE IF NOT EXISTS comment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment TEXT NOT NULL,
        lessonId INT NOT NULL,
        userId INT NOT NULL,
        status ENUM('active', 'flagged', 'deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (lessonId) REFERENCES lessons(id) ON DELETE CASCADE,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (status),
        INDEX (lessonId)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // comment_reply
    $sql = "CREATE TABLE IF NOT EXISTS comment_reply (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reply TEXT NOT NULL,
        commentId INT NOT NULL,
        userId INT NOT NULL,
        is_teacher_reply BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (commentId) REFERENCES comment(id) ON DELETE CASCADE,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (commentId),
        INDEX (is_teacher_reply)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    $sql = "CREATE TABLE IF NOT EXISTS comment_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (comment_id) REFERENCES comment(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (user_id, is_read)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // course_feedback
    $sql = "CREATE TABLE IF NOT EXISTS course_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        text TEXT,
        ratingCount INT,
        courseId INT,
        userId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (courseId) REFERENCES courses(id),
        FOREIGN KEY (userId) REFERENCES users(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;
    // enroll_course
    $sql = "CREATE TABLE IF NOT EXISTS enroll_course (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        courseId INT NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (courseId) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE (userId, courseId) -- Prevents duplicate enrollments
    ) ENGINE=InnoDB";

    if ($mysqli->query($sql) === false) return false;

    // payment_type - Only KPay and Cash
    $sql = "CREATE TABLE IF NOT EXISTS payment_type (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name ENUM('kpay', 'cash') NOT NULL UNIQUE, -- Only these two options
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";

    if ($mysqli->query($sql) === false) return false;

    // enroll_payment
    $sql = "CREATE TABLE IF NOT EXISTS enroll_payment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        paymentTypeId INT NOT NULL,
        enroll_courseId INT NOT NULL,
        transitionId VARCHAR(100) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL, -- Better for currency than INT
        screenshot_path VARCHAR(255) NULL, -- Only for KPay
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (paymentTypeId) REFERENCES payment_type(id),
        FOREIGN KEY (enroll_courseId) REFERENCES enroll_course(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";

    if ($mysqli->query($sql) === false) return false;

    // Insert the two payment types if they don't exist
    $sql = "INSERT IGNORE INTO payment_type (name, description) VALUES 
        ('kpay', 'KBZ Pay Mobile Payment'),
        ('cash', 'Cash Payment')";
    if ($mysqli->query($sql) === false) return false;
    
    // module
    $sql = "CREATE TABLE IF NOT EXISTS module (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // question
    $sql = "CREATE TABLE IF NOT EXISTS question (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT,
        moduleId INT,
        title VARCHAR(200),
        userId INT,
        likeCount INT DEFAULT 0,
        is_approve BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (moduleId) REFERENCES module(id),
        FOREIGN KEY (userId) REFERENCES users(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // answer
    $sql = "CREATE TABLE IF NOT EXISTS answer (
        id INT AUTO_INCREMENT PRIMARY KEY,
        answer TEXT,
        userId INT,
        questionId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id),
        FOREIGN KEY (questionId) REFERENCES question(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    $sql = "CREATE TABLE IF NOT EXISTS question_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        questionId INT,
        userId INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (questionId) REFERENCES question(id) ON DELETE CASCADE,
        FOREIGN KEY (userId) REFERENCES users(id),
        UNIQUE KEY unique_like (questionId, userId)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // question_notifications
    $sql = "CREATE TABLE IF NOT EXISTS question_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        user_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES question(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (user_id, is_read)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;
    
    //approveby
    // $sql = "CREATE TABLE IF NOT EXISTS approveby (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     userId INT,
    //     questionId INT,
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     FOREIGN KEY (userId) REFERENCES users(id),
    //     FOREIGN KEY (questionId) REFERENCES question(id)
    // )";
    // if ($mysqli->query($sql) === false) return false;
    

    // learning_path
    $sql = "CREATE TABLE IF NOT EXISTS learning_path (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            thumbnail_url VARCHAR(255),
            difficulty ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
            category ENUM('Programming', 'Design', 'Business', 'Data Science', 'Marketing') DEFAULT 'Programming',
            is_featured BOOLEAN DEFAULT FALSE,
            is_certificate BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            total_enrollments INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // learning_path_courseId
    $sql = "CREATE TABLE IF NOT EXISTS learning_path_courseid (
        id INT AUTO_INCREMENT PRIMARY KEY,
        learning_pathId INT,
        courseId INT,
        sequence INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (learning_pathId) REFERENCES learning_path(id),
        FOREIGN KEY (courseId) REFERENCES courses(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    //check db for complete
    $sql = "CREATE TABLE IF NOT EXISTS user_learning_path_course (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        learning_path_courseid_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (learning_path_courseid_id) REFERENCES learning_path_courseid(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    
    // blog
    $sql = "CREATE TABLE IF NOT EXISTS blog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200),
        description TEXT,
        blogCatagory ENUM('general','announcement','tech','education') NOT NULL,
        thumbnail VARCHAR(255),
        slug VARCHAR(255),
        authorName VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    // about_us
    $sql = "CREATE TABLE IF NOT EXISTS about_us (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100),
        logo VARCHAR(200),
        about TEXT,
        phone VARCHAR(50),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    $sql = "CREATE TABLE IF NOT EXISTS schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_link TEXT,
        datetime DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    $sql = "CREATE TABLE IF NOT EXISTS schedule_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT,
        scheduleId INT,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        schdule_role ENUM('student', 'teacher') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id),
        FOREIGN KEY (scheduleId) REFERENCES schedule(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;


    $sql = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        issue_date DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;
    
    $sql = "CREATE TABLE IF NOT EXISTS certificate_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        download_date DATETIME NOT NULL,
        FOREIGN KEY (certificate_id) REFERENCES certificates(certificate_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )ENGINE=InnoDB";
    if ($mysqli->query($sql) === false) return false;

    
}

