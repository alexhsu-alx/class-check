<?php
session_start();

?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>九大行星介紹網站</title>
  <link rel="stylesheet" href="video.css" />
  <style>
    .video-back {
      background-attachment: fixed;
      position: absolute;
      top: 60px;
      left: 0;
      object-fit: cover;
      z-index: -1;
      width: 100%;
      height: 100%;
    }
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }
    header {
      background-color: black;
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      z-index: 10;
    }
    .logo {
      font-size: 24px;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .user-info {
      display: flex;
      align-items: center;
      cursor: pointer;
      user-select: none;
    }
    .user-info a {
      display: flex;
      align-items: center;
      color: white;
      text-decoration: none;
    }
    .user-info img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-left: 10px;
      border: 2px solid white;
      transition: transform 0.3s;
      object-fit: cover;
    }
    .user-info a:hover img {
      transform: scale(1.1);
    }
    .user-info span {
      font-weight: bold;
      white-space: nowrap;
    }
    .nav-links {
      display: flex;
      gap: 15px;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    .nav-links a:hover {
      color: #ff7f50;
    }

    .dropdown {
      position: relative;
    }

    .dropdown > a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      cursor: pointer;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      top: 30px;
      right: 0;
      background-color: #333;
      border: 1px solid #555;
      border-radius: 8px;
      min-width: 140px;
      box-shadow: 0 0 10px #000;
      z-index: 99;
      flex-direction: column;
    }

    .dropdown-content a {
      padding: 10px;
      color: white;
      text-decoration: none;
      display: block;
    }

    .dropdown-content a:hover {
      background-color: #444;
      color: #ff7f50;
    }

    .dropdown:hover .dropdown-content {
      display: flex;
    }

    .planet-container {
      display: flex;
      flex-wrap: nowrap;
      justify-content: flex-start;
      overflow-x: auto;
      gap: 40px;
      margin-top: 500px;
      padding: 40px;
    }
    .planet {
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
      width: 140px;
    }
    .planet img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      transition: transform 0.3s, box-shadow 0.3s;
      border: 2px solid white;
    }
    .planet h2 {
      margin-top: 10px;
      font-size: 20px;
    }
    .planet:hover {
      transform: scale(1.1);
    }
    .planet:hover img {
      transform: scale(1.2);
      box-shadow: 0 0 15px #ff7f50;
    }
    a {
      color: inherit;
      text-decoration: none;
    }
    a:hover h2 {
      color: #ff7f50;
    }
  </style>
</head>
<body>

  <header>
    <div class="logo">九大行星</div>
    <div class="header-right">
      <nav class="nav-links">
        <a href="https://www.onlinegames.io/google-pacman/" target="_blank">遊戲</a>
        <a href="forum.php">論壇</a>
        
        <?php if (!empty($_SESSION['username'])): ?>
          <a href="logout.php">登出</a>
        <?php else: ?>
          <a href="register.php">註冊/登入</a>
        <?php endif; ?>
      </nav>

      <?php if (!empty($_SESSION['username'])): ?>
        <div class="user-info">
          <a href="profile.php" title="點擊修改個人資料">
            <span><?= htmlspecialchars($_SESSION['username']) ?></span>

          </a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <video class="video-back" autoplay loop muted>
    <source src="video.mp4" type="video/mp4" />
  </video>

  <div class="planet-container">
    <?php
    $planets = [
      ['name' => '水星', 'url' => 'https://zh.wikipedia.org/wiki/水星', 'img' => 'images/水星.png'],
      ['name' => '金星', 'url' => 'https://zh.wikipedia.org/wiki/金星', 'img' => 'images/金星.png'],
      ['name' => '地球', 'url' => 'https://zh.wikipedia.org/wiki/地球', 'img' => 'images/地球.png'],
      ['name' => '火星', 'url' => 'https://zh.wikipedia.org/wiki/火星', 'img' => 'images/火星.png'],
      ['name' => '木星', 'url' => 'https://zh.wikipedia.org/wiki/木星', 'img' => 'images/木星.png'],
      ['name' => '土星', 'url' => 'https://zh.wikipedia.org/wiki/土星', 'img' => 'images/土星.png'],
      ['name' => '天王星', 'url' => 'https://zh.wikipedia.org/wiki/天王星', 'img' => 'images/天王星.png'],
      ['name' => '海王星', 'url' => 'https://zh.wikipedia.org/wiki/海王星', 'img' => 'images/海王星.png'],
      ['name' => '冥王星', 'url' => 'https://zh.wikipedia.org/wiki/冥王星', 'img' => 'images/冥王星.png'],
    ];
    foreach ($planets as $planet): ?>
      <div class="planet">
        <a href="<?= $planet['url'] ?>" target="_blank">
          <img src="<?= $planet['img'] ?>" alt="<?= $planet['name'] ?>" />
          <h2><?= $planet['name'] ?></h2>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>
