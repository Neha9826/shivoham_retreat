<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../config.php';

?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Core</div>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <!-- <div class="sb-sidenav-menu-heading">Interface</div> -->
                <a class="nav-link" href="<?= BASE_URL ?>bookingRequests.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                    Booking Requests
                </a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#room" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Rooms
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="room" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addRoom.php">Add Rooms</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allRooms.php">All Rooms</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#amnty" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Amenities
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="amnty" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addAmenity.php">Add Amenity</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allAmenities.php">All Amenities</a>
                    </nav>
                </div>
                <!-- <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Meal Plans
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="createMeal.php">Create Meal Plan</a>
                        <a class="nav-link" href="allMeals.php">All Meal Plans</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#extraB" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Extra Beds
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="extraB" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="addExtraBed.php">Add Extra Bed</a>
                        <a class="nav-link" href="extraBedList.php">All Extra Beds</a>
                    </nav>
                </div> -->
                <a class="nav-link" href="<?= BASE_URL ?>yoga/index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                    Yoga
                </a>
                <!-- <div class="collapse" id="yogaDropdown" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="addYogaDropdown.php">Add Yoga Option</a>
                        <a class="nav-link" href="allYogaDropdown.php">All Yoga Options</a>
                    </nav>
                </div> -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#about" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        About
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="about" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addAbout.php">Add About</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allAbout.php">All About</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#cancellation" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Cancellation Policy
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="cancellation" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addCancellationPolicy.php">Add Cancellation Policy</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allCancellationPolicies.php">All Cancellation Policies</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#contact" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Contact
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="contact" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>contact_info.php">Add Contact Info</a>
                        <a class="nav-link" href="<?= BASE_URL ?>contact_messages.php">Contact Messages</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#blog" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Blog
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="blog" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addBlog.php">Add Blog</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allBlogs.php">All Blog</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#nearby" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Nearby Places
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="nearby" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addNearby.php">Add Nearby Place</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allNearby.php">All Nearby Places</a>
                    </nav>
                </div>
                <!-- <a class="nav-link" href="query.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Query Requests
                </a> -->
                <!-- <div class="sb-sidenav-menu-heading">Addons</div> -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#emp" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Employees
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="emp" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>addEmp.php">Add Employee</a>
                        <a class="nav-link" href="<?= BASE_URL ?>allEmp.php">All Employees</a>
                    </nav>
                </div>
                
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?= isset($_SESSION['emp_name']) ? htmlspecialchars($_SESSION['emp_name']) : 'Unknown User'; ?>
        </div>
    </nav>
</div>