<div class="offers_area">
        <div class="container">
            <div class="row room-card">
                <div class="col-xl-12">
                    <div class="section_title text-center mb-100">
                        <span>Available Rooms</span>
                        <h3>Our Best Rooms</h3>
                    </div>
                </div>
            </div>
            <div class="row room-cards ">
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="col-xl-4 col-md-4">
                    <div class="single_offers">
                        <div class="about_thumb  flex-fill">
                            <?php
                                $roomId = $room['id'];
                                $imageSql = "SELECT image_path FROM room_images WHERE room_id = $roomId LIMIT 1";
                                $imageResult = $conn->query($imageSql);

                                $imageRow = ($imageResult && $imageResult->num_rows > 0) ? $imageResult->fetch_assoc() : null;
                                $imagePath = $imageRow['image_path'] ?? 'assets/img/default-room.jpg';

                                // Check if image path already starts with 'uploads/', then prepend 'admin/' because you're outside admin folder
                                if ($imagePath && str_starts_with($imagePath, 'uploads/')) {
                                    $imageSrc = 'admin/' . $imagePath;
                                } else {
                                    $imageSrc = $imagePath;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Room Image" style="width:100%; height:250px; object-fit:cover;">
                        </div>
                        <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                        <ul>
                            <li>Capacity: <?php echo htmlspecialchars($room['room_capacity']); ?> persons</li>
                            <li>Price: ₹<?php echo htmlspecialchars($room['price_per_night']); ?> / night</li>
                        </ul>
                        <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="book_now">Book Now</a>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        </div>
    </div>