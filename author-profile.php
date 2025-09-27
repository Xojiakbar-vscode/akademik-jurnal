<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: authors.php');
    exit;
}

$authorId = intval($_GET['id']);

// Muallif ma'lumotlarini olish
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(a.id) as article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'approved'
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$authorId]);
$author = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$author) {
    header('Location: authors.php');
    exit;
}

// Muallifning maqolalarini olish
$articles = $pdo->prepare("
    SELECT a.*, c.name as category_name 
    FROM articles a 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE a.author_id = ? AND a.status = 'approved' 
    ORDER BY a.published_at DESC
");
$articles->execute([$authorId]);
$authorArticles = $articles->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($author['name']); ?> - Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .article-card {
            transition: transform 0.3s;
        }
        .article-card:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light">
        <div class="container">
            <ol class="breadcrumb py-3">
                <li class="breadcrumb-item"><a href="index.php">Bosh sahifa</a></li>
                <li class="breadcrumb-item"><a href="authors.php">Mualliflar</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($author['name']); ?></li>
            </ol>
        </div>
    </nav>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php echo getAuthorImage($author); ?>" 
                         alt="<?php echo htmlspecialchars($author['name']); ?>" 
                         class="profile-img">
                </div>
                <div class="col-md-9">
                    <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($author['name']); ?></h1>
                    <?php if (!empty($author['affiliation'])): ?>
                        <p class="lead mb-2"><?php echo htmlspecialchars($author['affiliation']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($author['orcid_id'])): ?>
                        <p class="mb-1"><strong>ORCID:</strong> <?php echo htmlspecialchars($author['orcid_id']); ?></p>
                    <?php endif; ?>
                    <div class="d-flex gap-3 mt-3">
                        <div class="stat-card">
                            <h4 class="text-primary mb-0"><?php echo $author['article_count']; ?></h4>
                            <small>Maqolalar</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ma'lumotlar</h5>
                            <?php if (!empty($author['email'])): ?>
                                <p><strong>Email:</strong><br><?php echo htmlspecialchars($author['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($author['website'])): ?>
                                <p><strong>Vebsayt:</strong><br>
                                    <a href="<?php echo htmlspecialchars($author['website']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($author['website']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($author['research_interests'])): ?>
                                <p><strong>Tadqiqot sohalari:</strong><br>
                                    <?php echo htmlspecialchars($author['research_interests']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Bio -->
                    <?php if (!empty($author['bio'])): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Biografiya</h5>
                            <p><?php echo nl2br(htmlspecialchars($author['bio'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Maqolalar -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Nashr etilgan maqolalar (<?php echo $author['article_count']; ?>)</h5>
                            <?php if (count($authorArticles) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($authorArticles as $article): ?>
                                    <a href="article.php?id=<?php echo $article['id']; ?>" 
                                       class="list-group-item list-group-item-action article-card">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h6>
                                            <small><?php echo date('d.m.Y', strtotime($article['published_at'])); ?></small>
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <?php echo mb_substr(strip_tags($article['abstract']), 0, 150); ?>...
                                        </p>
                                        <?php if (!empty($article['category_name'])): ?>
                                            <span class="badge bg-primary"><?php echo $article['category_name']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Hozircha maqolalar mavjud emas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>
</body>
</html>
<?php
function getAuthorImage($author) {
    if (!empty($author['profile_image']) && file_exists('uploads/profiles/' . $author['profile_image'])) {
        return 'uploads/profiles/' . $author['profile_image'];
    }
    return 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60';
}
?>