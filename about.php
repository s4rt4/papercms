<?php
/**
 * About Me Page - Paper CMS
 */
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get site settings
$siteTitle = getSetting('site_title', 'Paper CMS');
$ownerName = getSetting('site_owner_name', 'John Doe');
$ownerBio = getSetting('site_owner_bio', 'I am a creative professional passionate about design, writing, and building beautiful things for the web.');
$ownerAvatar = getSetting('site_owner_avatar', 'assets/img/index_avatar-male-1.png');

// Get team members
$teamMembers = [];
try {
    $teamMembers = $db->query("SELECT * FROM team_members ORDER BY sort_order, id")->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet, use dummy data
    $teamMembers = [
        ['name' => 'Alex Johnson', 'position' => 'Designer', 'avatar' => 'assets/img/index_avatar-male-2.png'],
        ['name' => 'Sarah Parker', 'position' => 'Developer', 'avatar' => 'assets/img/index_avatar-female-1.png'],
        ['name' => 'Mike Chen', 'position' => 'Writer', 'avatar' => 'assets/img/index_avatar-male-3.png'],
        ['name' => 'Emily Davis', 'position' => 'Marketing', 'avatar' => 'assets/img/index_avatar-female-2.png'],
        ['name' => 'David Kim', 'position' => 'Support', 'avatar' => 'assets/img/index_avatar-male-4.png'],
        ['name' => 'Lisa Wong', 'position' => 'Manager', 'avatar' => 'assets/img/index_avatar-female-3.png'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Me - <?= e($siteTitle) ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body>
    <div class="page-content">
        <!-- Profile Section -->
        <section class="section profile-section">
            <img src="<?= e($ownerAvatar) ?>" alt="<?= e($ownerName) ?>" class="profile-avatar">
            <h1 class="profile-name"><?= e($ownerName) ?></h1>
            <p class="profile-bio"><?= e($ownerBio) ?></p>
        </section>

        <!-- My Team Section -->
        <section class="section">
            <h2 class="section-title">My Team</h2>
            <p class="text-center text-muted mb-3">Meet the amazing people I work with</p>
            
            <div class="team-grid">
                <?php foreach ($teamMembers as $member): ?>
                    <div class="team-card">
                        <img src="<?= e($member['avatar']) ?>" alt="<?= e($member['name']) ?>">
                        <h4><?= e($member['name']) ?></h4>
                        <p><?= e($member['position']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
