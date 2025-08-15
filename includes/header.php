<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <div class="header-area">
        <div id="sticky-header" class="main-header-area">
            <div class="container-fluid p-0">
                <div class="row align-items-center no-gutters">
                    <div class="col-xl-5 col-lg-6">
                        <div class="main-menu d-none d-lg-block">
                            <nav>
                                <ul id="navigation">
                                    <li><a class="<?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">Home</a></li>
                                    <li><a class="<?= ($current_page == 'allRooms.php') ? 'active' : '' ?>" href="allRooms.php">Rooms</a></li>
                                    <li><a class="<?= ($current_page == 'about.php') ? 'active' : '' ?>" href="about.php">About</a></li>
                                    <li><a class="<?= ($current_page == 'blog.php') ? 'active' : '' ?>" href="blog.php">Blog</a></li>
                                    <li>
                                        <a href="#">Courses <i class="ti-angle-down"></i></a>
                                        <ul class="submenu">
                                            <li><a href="#">Coming Soon</a></li>
                                        </ul>
                                    </li>
                                    <li><a class="<?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="contact.php">Contact</a></li>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <li class="dropdown">
                                            <a href="#">Profile <i class="ti-angle-down"></i></a>
                                            <ul class="submenu">
                                                <li><a class="<?= ($current_page == 'profile.php') ? 'active' : '' ?>" href="profile.php">My Profile</a></li>
                                                <li><a href="logout.php">Logout</a></li>
                                            </ul>
                                        </li>
                                    <?php else: ?>
                                        <li><a class="<?= ($current_page == 'login.php') ? 'active' : '' ?>" href="login.php">Login</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-2">
                        <div class="logo-img">
                            <a href="admin/login.php">
                                <img src="img/Shivoham.png" alt="S" style="max-height: 80px; width: auto;">
                            </a>
                        </div>
                    </div>

                    <div class="col-xl-5 col-lg-4 d-none d-lg-block">
                        <div class="book_room">
                            <div class="socail_links">
                                <ul>
                                    <li><a href="#"><i class="fa fa-whatsapp"></i></a></li>
                                    <li><a href="#"><i class="fa fa-facebook-square"></i></a></li>
                                    <li><a href="https://www.instagram.com/retreatshivoham?igsh=MWd1MTg1emRqOHE3Ng=="><i class="fa fa-instagram"></i></a></li>
                                    <li><a href="#"><i class="fa fa-youtube"></i></a></li>
                                </ul>
                            </div>
                            <div class="book_btn d-none d-lg-block">
                                <a href="allRooms.php">Book A Room</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mobile_menu d-block d-lg-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
