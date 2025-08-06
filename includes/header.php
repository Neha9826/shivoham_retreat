<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="header-area ">
        <div id="sticky-header" class="main-header-area">
            <div class="container-fluid p-0">
                <div class="row align-items-center no-gutters">
                    <div class="col-xl-5 col-lg-6">
                        <div class="main-menu  d-none d-lg-block">
                            <nav>
                                <ul id="navigation">
                                    <li><a class="active" href="index.php">home</a></li>
                                    <li><a href="allRooms.php">rooms</a></li>
                                    <li><a href="about.php">About</a></li>
                                    <li><a href="#">blog <i class="ti-angle-down"></i></a>
                                        <ul class="submenu">
                                            <li><a href="#">blog</a></li>
                                            <li><a href="#">Reviews</a></li>
                                        </ul>
                                    </li>
                                    <li><a href="#">Courses <i class="ti-angle-down"></i></a>
                                        <ul class="submenu">
                                            <li><a href="#">Coming Soon</a></li>
                                        </ul>
                                    </li>
                                    <li><a href="#">Contact</a></li>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <li class="dropdown">
                                            <a href="#">Profile <i class="ti-angle-down"></i></a>
                                            <ul class="submenu">
                                                <li><a href="profile.php">My Profile</a></li>
                                                <li><a href="logout.php">Logout</a></li>
                                            </ul>
                                        </li>
                                    <?php else: ?>
                                        <li><a href="login.php">Login</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2">
                        <div class="logo-img">
                            <a href="admin/login.php">
                                <!-- <img src="img/Shivoham.png" alt="S"> -->
                                 <img src="img/Shivoham.png" alt="S" style="max-height: 80px; width: auto;">
                                    <!-- <h1>Shivoham</h1> -->
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-5 col-lg-4 d-none d-lg-block">
                        <div class="book_room">
                            <div class="socail_links">
                                <ul>
                                    <li>
                                    <a href="#">
                                        <i class="fa fa-whatsapp"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-facebook-square"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.instagram.com/retreatshivoham?igsh=MWd1MTg1emRqOHE3Ng== ">
                                        <i class="fa fa-instagram"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-youtube"></i>
                                    </a>
                                </li>
                                </ul>
                            </div>
                            <div class="book_btn d-none d-lg-block">
                                <a class="" href="allRooms.php">Book A Room</a>
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