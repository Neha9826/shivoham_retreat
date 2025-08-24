<?php
// submitBooking.php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve all data from the POST request
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $name = trim($firstName . ' ' . $lastName);
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $roomId = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        $checkIn = $_POST['check_in'] ?? '';
        $checkOut = $_POST['check_out'] ?? '';
        $noOfRooms = isset($_POST['no_of_rooms']) ? intval($_POST['no_of_rooms']) : 1;
        $guests = isset($_POST['guests']) ? intval($_POST['guests']) : 2;
        $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
        $mealPlanKey = $_POST['meal_plan'] ?? 'standard';
        $childAges = $_POST['child_ages'] ?? [];

        // Validate basic inputs
        if (empty($name) || empty($email) || empty($phone) || empty($checkIn) || empty($checkOut) || $roomId <= 0 || $noOfRooms < 1 || $guests < 1) {
            throw new Exception("Please fill all required fields and ensure valid selections.");
        }

        // --- NEW USER AUTHENTICATION LOGIC ---
        // Start transaction for user management
        $conn->begin_transaction();
        $userId = null;
        $tempPassword = '';

        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        } else {
            // Check if user exists by email or phone
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? OR phone = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare user check statement: " . $conn->error);
            }
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                // User exists, use their ID
                $userId = $user['id'];
                $_SESSION['user_id'] = $userId;
            } else {
                // User does not exist, create a new account
                $tempPassword = $phone; // Use phone number as temporary password
                $hashed_password = password_hash($tempPassword, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
                if (!$stmt_insert) {
                    throw new Exception("Failed to prepare new user statement: " . $conn->error);
                }
                $stmt_insert->bind_param("ssss", $name, $email, $phone, $hashed_password);
                if (!$stmt_insert->execute()) {
                    throw new Exception("Failed to create new user: " . $stmt_insert->error);
                }
                $userId = $conn->insert_id;
                $_SESSION['user_id'] = $userId;
                // Add new user details to session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
            }
            $stmt->close();
        }
        $conn->commit();
        // --- END NEW USER AUTHENTICATION LOGIC ---

        // Calculate number of nights
        function get_num_nights($checkIn, $checkOut)
        {
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
        $totalBaseAdultsCapacity = $roomDetails['base_adults'] * $noOfRooms;
        $extraAdultsNeedingBeds = max(0, $guests - $totalBaseAdultsCapacity);

        $totalAllowedExtraBeds = $roomDetails['max_extra_with_bed'] * $noOfRooms;
        $totalExtraBedsNeeded = min($extraAdultsNeedingBeds, $totalAllowedExtraBeds);

        $totalChildrenBelow5Capacity = $roomDetails['max_child_without_bed_below_5'] * $noOfRooms;
        $totalChildren5_12Capacity = $roomDetails['max_child_without_bed_5_12'] * $noOfRooms;

        if ($children_below_5_count > $totalChildrenBelow5Capacity) {
            throw new Exception('Number of children below 5 exceeds room capacity. Please adjust.');
        }
        if ($children_5_12_count > $totalChildren5_12Capacity) {
            throw new Exception('Number of children 5-12 exceeds room capacity. Please adjust.');
        }

        // Check if total guests exceed total capacity
        $maxTotalGuestsAllowed = ($roomDetails['base_adults'] * $noOfRooms) +
            ($roomDetails['max_child_without_bed_below_5'] * $noOfRooms) +
            ($roomDetails['max_child_without_bed_5_12'] * $noOfRooms) +
            ($roomDetails['max_extra_with_bed'] * $noOfRooms);

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
        $stmt_check->close();

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
        $stmt_prices->close();
        $fallbackPriceColumn = ($mealPlanKey === 'standard') ? 'standard_price' : 'price_with_' . $mealPlanKey;
        $basePricePerNight = $seasonal_prices[$priceColumn] ?? $roomDetails[$fallbackPriceColumn];

        $roomCost = $basePricePerNight * $noOfRooms * $numNights;
        $extraBedCost = $totalExtraBedsNeeded * $roomDetails['price_with_extra_bed'] * $numNights;
        $totalChildCost = ($children_5_12_count * $roomDetails['price_child_5_12'] + $children_below_5_count * $roomDetails['price_child_below_5']) * $numNights;
        $totalAmount = $roomCost + $extraBedCost + $totalChildCost;

        // Prepare child ages for storage as JSON
        $childAgesJson = json_encode($childAges);

        // Start transaction for booking insertion
        $conn->begin_transaction();

        // Insert booking into the database
        $sql = "INSERT INTO bookings (room_id, user_id, name, email, phone, check_in, check_out, no_of_rooms, guests, children, extra_beds, meal_plan, total_price, child_ages_json, room_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for bookings: " . $conn->error);
        }

        $stmt->bind_param("iisssssiiiisdss", $roomId, $userId, $name, $email, $phone, $checkIn, $checkOut, $noOfRooms, $guests, $children, $totalExtraBedsNeeded, $mealPlanKey, $totalAmount, $childAgesJson, $roomDetails['room_name']);

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement for bookings: " . $stmt->error);
        }

        $bookingId = $conn->insert_id;
        $stmt->close();

        // Insert into booking_rooms for each room booked
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
            $stmt_booking_room->close();
        }

        $conn->commit();

        // Send email with temporary password
        if (!empty($tempPassword)) {
            $subject = 'Your Account at Shivoham Retreat';
            $message = "Dear {$firstName},\n\nYour booking is confirmed. We have created an account for you.\n\nYour temporary password is: {$tempPassword}\n\nPlease log in and change your password as soon as possible for security.\n\nThank you,\nShivoham Retreat";
            $headers = 'From: noreply@shivohamretreat.com' . "\r\n" .
                'Reply-To: noreply@shivohamretreat.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            // mail($email, $subject, $message, $headers);
        }

        // Set success response
        $response['success'] = true;
        $response['message'] = 'Booking successful! Redirecting...';
        $response['redirect_url'] = 'viewBooking.php?id=' . $bookingId;
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
        }
        $response['message'] = 'Booking failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>