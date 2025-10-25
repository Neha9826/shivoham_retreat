<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="sidebar-header px-3 py-3 fw-bold fs-5 border-bottom">
    <i class="bi bi-flower3 me-2 text-primary"></i> Host Panel
  </div>
  <ul class="list-unstyled components">

    <!-- Organization -->
    <li>
      <a href="#orgMenu" data-bs-toggle="collapse" class="d-flex align-items-center justify-content-between px-3 py-2">
        <span><i class="bi bi-building me-2"></i> Organization</span>
        <i class="bi bi-caret-down-fill small"></i>
      </a>
      <ul class="collapse list-unstyled ps-4" id="orgMenu">
        <li><a href="register_org.php" class="d-block px-3 py-2">➕ Register</a></li>
        <li><a href="all_org.php" class="d-block px-3 py-2">📋 View</a></li>
      </ul>
    </li>

    <!-- Retreats -->
    <li>
      <a href="#retreatMenu" data-bs-toggle="collapse" class="d-flex align-items-center justify-content-between px-3 py-2">
        <span><i class="bi bi-sun me-2"></i> Retreats</span>
        <i class="bi bi-caret-down-fill small"></i>
      </a>
      <ul class="collapse list-unstyled ps-4" id="retreatMenu">
        <li><a href="create_retreat.php" class="d-block px-3 py-2">➕ Create</a></li>
        <li><a href="all_host_retreats.php" class="d-block px-3 py-2">📋 View</a></li>
      </ul>
    </li>

    <!-- Packages -->
    <li>
      <a href="#packageMenu" data-bs-toggle="collapse" class="d-flex align-items-center justify-content-between px-3 py-2">
        <span><i class="bi bi-box-seam me-2"></i> Packages</span>
        <i class="bi bi-caret-down-fill small"></i>
      </a>
      <ul class="collapse list-unstyled ps-4" id="packageMenu">
        <li><a href="createPackage.php" class="d-block px-3 py-2">➕ Create</a></li>
        <li><a href="allHostPackages.php" class="d-block px-3 py-2">📋 View</a></li>
      </ul>
    </li>

    <!-- Batches -->
    <li>
      <a href="#batchMenu" data-bs-toggle="collapse" class="d-flex align-items-center justify-content-between px-3 py-2">
        <span><i class="bi bi-people me-2"></i> Batches</span>
        <i class="bi bi-caret-down-fill small"></i>
      </a>
      <ul class="collapse list-unstyled ps-4" id="batchMenu">
        <li><a href="createBatch.php" class="d-block px-3 py-2">➕ Create</a></li>
        <li><a href="allHostBatches.php" class="d-block px-3 py-2">📋 View</a></li>
      </ul>
    </li>

    <!-- Instructors -->
    <li>
      <a href="#instructorMenu" data-bs-toggle="collapse" class="d-flex align-items-center justify-content-between px-3 py-2">
        <span><i class="bi bi-person-badge me-2"></i> Instructors</span>
        <i class="bi bi-caret-down-fill small"></i>
      </a>
      <ul class="collapse list-unstyled ps-4" id="instructorMenu">
        <li><a href="org_instructor.php" class="d-block px-3 py-2">➕ Add</a></li>
        <li><a href="all_instructors.php" class="d-block px-3 py-2">📋 View</a></li>
      </ul>
    </li>

  </ul>
</nav>
