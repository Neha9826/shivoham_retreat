<?php
// submitBooking.php
session_start();
include 'db.php';

// IMPORTANT: For this code to work, you need to modify your 'bookings' table.
// If you haven't already, please run the following SQL commands:
// ALTER TABLE `bookings` ADD `no_of_rooms` INT(11) NOT NULL DEFAULT 1;
// ALTER TABLE `bookings` CHANGE `meal_plan_id` `meal_plan` VARCHAR(50) NOT NULL;
// ALTER TABLE `bookings` ADD `child_ages_json` JSON NULL;
// ALTER TABLE `bookings` ADD `room_name` VARCHAR(255) NULL;
// CREATE TABLE `booking_rooms` (
//  `id` INT(11) NOT NULL AUTO_INCREMENT,
//  `booking_id` INT(11) NOT NULL,
//  `room_id` INT(11) NOT NULL,
//  `assigned_date` DATE NOT NULL,
//  PRIMARY KEY (`id`),
//  KEY `booking_id` (`booking_id`),
//  KEY `room_id` (`room_id`),
//  CONSTRAINT `booking_rooms_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
//  CONSTRAINT `booking_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve all data from the POST request
        $firstName   = $_POST['first_name'] ?? '';
        $lastName    = $_POST['last_name'] ?? '';
        $name        = $firstName . ' ' . $lastName;
        $email       = $_POST['email'] ?? '';
        $phone       = $_POST['phone'] ?? '';
        $roomId      = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        $checkIn     = $_POST['check_in'] ?? '';
        $checkOut    = $_POST['check_out'] ?? '';
        $noOfRooms   = isset($_POST['no_of_rooms']) ? intval($_POST['no_of_rooms']) : 1;
        $guests      = isset($_POST['guests']) ? intval($_POST['guests']) : 2;
        $children    = isset($_POST['children']) ? intval($_POST['children']) : 0;
        $mealPlanKey = $_POST['meal_plan'] ?? 'standard';
        $childAges   = $_POST['child_ages'] ?? [];

        // Validate basic inputs
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($checkIn) || empty($checkOut) || $roomId <= 0 || $noOfRooms < 1 || $guests < 1) {
            throw new Exception("Please fill all required fields and ensure valid selections.");
        }

        // Calculate number of nights
        function get_num_nights($checkIn, $checkOut) {
            if (!$checkIn || !$checkOut) return 0;
            $date1 = new DateTime($checkIn);
            $date2 = new DateTime($checkOut);
            $diff = $date1->diff($date2);
            return max(1, $diff->days);
        }

        $numNights = get_num_nights($checkIn, $checkOut);

        if ($numNights === 0) {
            throw new Exception('Invalid check-in/check-out dates.');
        }

        // Fetch room details for capacity and price calculation
        $roomSql = "SELECT * FROM rooms WHERE id = ?";
        $stmt = $conn->prepare($roomSql);
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $roomDetails = $stmt->get_result()->fetch_assoc();

        if (!$roomDetails) {
            throw new Exception('Room not found.');
        }

        // Re-run the core price calculation logic from calculateBookingPrice.php
        $totalExtraBedsNeeded = 0;
        $children_5_12_count = 0;
        $children_below_5_count = 0;

        foreach ($childAges as $age) {
            if ($age == 1) { // Age 5-12
                $children_5_12_count++;
            } else { // Age below 5
                $children_below_5_count++;
            }
        }
        
        // Per-room guest allocation and extra bed calculation
        // Calculate total base adults capacity for all rooms
        $totalBaseAdultsCapacity = $roomDetails['base_adults'] * $noOfRooms;
        // Calculate extra adults needing beds
        $extraAdultsNeedingBeds = max(0, $guests - $totalBaseAdultsCapacity);

        // Ensure extra adults needing beds does not exceed total max_extra_with_bed for all rooms
        $totalAllowedExtraBeds = $roomDetails['max_extra_with_bed'] * $noOfRooms;
        $totalExtraBedsNeeded = min($extraAdultsNeedingBeds, $totalAllowedExtraBeds);

        // Capacity check for children without beds (they don't consume extra beds directly, but room capacity)
        $totalChildrenBelow5Capacity = $roomDetails['max_child_without_bed_below_5'] * $noOfRooms;
        $totalChildren5_12Capacity = $roomDetails['max_child_without_bed_5_12'] * $noOfRooms;

        if ($children_below_5_count > $totalChildrenBelow5Capacity) {
            throw new Exception('Number of children below 5 exceeds room capacity. Please adjust.');
        }
        if ($children_5_12_count > $totalChildren5_12Capacity) {
            throw new Exception('Number of children 5-12 exceeds room capacity. Please adjust.');
        }

        // Check if total guests exceed total capacity (base adults + all children + all extra beds)
        $maxTotalGuestsAllowed = ($roomDetails['base_adults'] * $noOfRooms) + 
                                 ($roomDetails['max_child_without_bed_below_5'] * $noOfRooms) + 
                                 ($roomDetails['max_child_without_bed_5_12'] * $noOfRooms) + 
                                 ($roomDetails['max_extra_with_bed'] * $noOfRooms); // Total extra bed slots
                                 
        if (($guests + $children) > $maxTotalGuestsAllowed) {
            throw new Exception('Your total guest count (Adults + Children) exceeds the maximum capacity for the number of rooms selected. Please reduce the number of guests or increase rooms.');
        }


        // Server-side real-time availability check before booking
        $conflictSql = "
            SELECT COUNT(*) AS booked_count
            FROM booking_rooms br
            JOIN bookings b ON br.booking_id = b.id
            WHERE br.room_id = ?
              AND (b.check_in < ? AND b.check_out > ?)
        ";
        $stmt_check = $conn->prepare($conflictSql);
        $stmt_check->bind_param("iss", $roomId, $checkOut, $checkIn);
        $stmt_check->execute();
        $bookedCount = $stmt_check->get_result()->fetch_assoc()['booked_count'];

        if (($roomDetails['total_rooms'] - $bookedCount) < $noOfRooms) {
            throw new Exception('Not enough rooms available for the selected dates and quantity. Please adjust your selection.');
        }


        // Price calculation
        $dayOfWeek = date('l', strtotime($checkIn));
        $priceColumn = strtolower($dayOfWeek) . '_' . $mealPlanKey;
        $sql_prices = "SELECT `{$priceColumn}` FROM room_seasonal_prices WHERE room_id = ? AND ? BETWEEN start_date AND end_date LIMIT 1";
        $stmt_prices = $conn->prepare($sql_prices);
        $stmt_prices->bind_param("is", $roomId, $checkIn);
        $stmt_prices->execute();
        $seasonal_prices = $stmt_prices->get_result()->fetch_assoc();
        $fallbackPriceColumn = ($mealPlanKey === 'standard') ? 'standard_price' : 'price_with_' . $mealPlanKey;
        $basePricePerNight = $seasonal_prices[$priceColumn] ?? $roomDetails[$fallbackPriceColumn];

        $roomCost = $basePricePerNight * $noOfRooms * $numNights;
        $extraBedCost = $totalExtraBedsNeeded * $roomDetails['price_with_extra_bed'] * $numNights;
        $totalChildCost = ($children_5_12_count * $roomDetails['price_child_5_12'] + $children_below_5_count * $roomDetails['price_child_below_5']) * $numNights;
        $totalAmount = $roomCost + $extraBedCost + $totalChildCost;

        // Prepare child ages for storage as JSON
        $childAgesJson = json_encode($childAges);

        // Start transaction
        $conn->begin_transaction();
        
        // Insert booking into the database
        $sql = "INSERT INTO bookings (room_id, name, email, phone, check_in, check_out, no_of_rooms, guests, children, extra_beds, meal_plan, total_price, child_ages_json, room_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for bookings: " . $conn->error);
        }

        // $stmt->bind_param("isssssiiisds", $roomId, $name, $email, $phone, $checkIn, $checkOut, $noOfRooms, $guests, $children, $totalExtraBedsNeeded, $mealPlanKey, $totalAmount, $childAgesJson, $roomDetails['room_name']);
        $stmt->bind_param("isssssiiiisdss", $roomId, $name, $email, $phone, $checkIn, $checkOut, $noOfRooms, $guests, $children, $totalExtraBedsNeeded, $mealPlanKey, $totalAmount, $childAgesJson, $roomDetails['room_name']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement for bookings: " . $stmt->error);
        }

        $bookingId = $conn->insert_id;

        // Insert into booking_rooms for each room booked (important for availability tracking)
        for ($i = 0; $i < $noOfRooms; $i++) {
            $sql_booking_room = "INSERT INTO booking_rooms (booking_id, room_id) VALUES (?, ?)"; 
            $stmt_booking_room = $conn->prepare($sql_booking_room);
            if (!$stmt_booking_room) {
                throw new Exception("Failed to prepare booking_rooms statement: " . $conn->error);
            }
            $stmt_booking_room->bind_param("ii", $bookingId, $roomId);
            if (!$stmt_booking_room->execute()) {
                throw new Exception("Failed to execute booking_rooms statement: " . $stmt_booking_room->error);
            }
        }

        $conn->commit();
        
        // Set success response
        $response['success'] = true;
        $response['message'] = 'Booking successful! Redirecting...';
        $response['redirect_url'] = 'viewBooking.php?id=' . $bookingId;

    } catch (Exception $e) {
        if ($conn) { // Check if connection exists before rollback
             $conn->rollback();
        }
        $response['message'] = 'Booking failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);