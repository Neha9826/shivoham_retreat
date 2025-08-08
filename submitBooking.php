<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_rooms  = $_POST['selected_rooms'] ?? [];
    $check_in        = $_POST['check_in'];
    $check_out       = $_POST['check_out'];
    $no_of_adults    = intval($_POST['guests']);
    $no_of_children  = intval($_POST['children']);
    $meal_plan_ids   = $_POST['meal_plan_id'] ?? [];
    $extra_beds      = $_POST['extra_beds'] ?? [];
    $total_price     = floatval(str_replace('₹', '', $_POST['total_price']));
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $status          = "pending";

    // ✅ Prevent past date bookings
    $today = date('Y-m-d');
    if ($check_in < $today || $check_out <= $check_in) {
        $_SESSION['booking_error'] = "Invalid dates. Please choose valid future dates.";
        header("Location: bookingForm.php");
        exit;
    }

    // ✅ Count how many times each room is selected
    $roomCountMap = array_count_values($selected_rooms);
    $main_room_id = intval($selected_rooms[0]);
    $total_room_count = array_sum($roomCountMap);

    // ✅ Validate availability
    foreach ($roomCountMap as $room_id => $count) {
        $conflictSql = "SELECT SUM(no_of_rooms) AS booked FROM bookings 
                        WHERE room_id = ? 
                          AND (
                              (? BETWEEN check_in AND DATE_SUB(check_out, INTERVAL 1 DAY)) OR
                              (? BETWEEN DATE_ADD(check_in, INTERVAL 1 DAY) AND check_out) OR
                              (check_in <= ? AND check_out >= ?)
                          )";
        $stmt = $conn->prepare($conflictSql);
        $stmt->bind_param("issss", $room_id, $check_in, $check_out, $check_in, $check_out);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_assoc()['booked'] ?? 0;
        $stmt->close();

        $roomQuery = mysqli_query($conn, "SELECT total_rooms FROM rooms WHERE id = $room_id");
        $roomData = mysqli_fetch_assoc($roomQuery);
        $available = intval($roomData['total_rooms']) - intval($booked);

        if ($available < $count) {
            $_SESSION['booking_error'] = "Room ID {$room_id} has only {$available} available. Please reduce your selection.";
            header("Location: bookingForm.php?check_in=$check_in&check_out=$check_out");
            exit;
        }
    }

    // ✅ Insert into bookings table
    $extra_beds_count = count($extra_beds);
    $age_group_str = !empty($extra_beds) ? implode(",", $extra_beds) : null;

    $stmt = $conn->prepare("INSERT INTO bookings 
        (room_id, check_in, check_out, no_of_rooms, guests, children, extra_beds, extra_bed_age_group_id, total_price, name, email, phone, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "issiiiisdssss",
        $main_room_id, $check_in, $check_out,
        $total_room_count, $no_of_adults, $no_of_children,
        $extra_beds_count, $age_group_str, $total_price,
        $name, $email, $phone, $status
    );

    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        $stmt->close();

        // ✅ booking_rooms
        $roomStmt = $conn->prepare("INSERT INTO booking_rooms (booking_id, room_id) VALUES (?, ?)");
        foreach ($selected_rooms as $r_id) {
            $roomStmt->bind_param("ii", $booking_id, $r_id);
            $roomStmt->execute();
        }
        $roomStmt->close();

        // ✅ booking_extra_beds
        if (!empty($extra_beds)) {
            $bedStmt = $conn->prepare("INSERT INTO booking_extra_beds (booking_id, extra_bed_id) VALUES (?, ?)");
            foreach ($extra_beds as $bed_id) {
                $bedStmt->bind_param("ii", $booking_id, $bed_id);
                $bedStmt->execute();
            }
            $bedStmt->close();
        }

        // ✅ booking_meal_plans
        if (!empty($meal_plan_ids)) {
            $mealStmt = $conn->prepare("INSERT INTO booking_meal_plans (booking_id, meal_plan_id) VALUES (?, ?)");
            foreach ($meal_plan_ids as $mp_id) {
                $mealStmt->bind_param("ii", $booking_id, $mp_id);
                $mealStmt->execute();
            }
            $mealStmt->close();
        }

        // ✅ User account logic
        $userExistsQuery = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $userExistsQuery->bind_param("ss", $email, $phone);
        $userExistsQuery->execute();
        $userExistsQuery->store_result();

        $newUserMessage = null;

        if ($userExistsQuery->num_rows === 0) {
            $hashedPassword = password_hash($phone, PASSWORD_DEFAULT);
            $createUserStmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $createUserStmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
            $createUserStmt->execute();
            $createUserStmt->close();

            $newUserMessage = "You have successfully registered as a valuable member of our organisation and your login id is: {$email} and your temp password is: {$phone}. You can change it anytime in the profile section.";

            // If this booking was for themselves (no active user OR matches logged-in email), log them in
            if (!isset($_SESSION['user_id']) || $_SESSION['user_email'] === $email) {
                $userLookup = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
                $userLookup->bind_param("s", $email);
                $userLookup->execute();
                $userResult = $userLookup->get_result();
                if ($userData = $userResult->fetch_assoc()) {
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['user_name'] = $userData['name'];
                    $_SESSION['user_email'] = $userData['email'];
                }
                $userLookup->close();
            }
        }
        $userExistsQuery->close();

        // ✅ Redirect with optional message
        $redirectUrl = "viewBooking.php?booking_id={$booking_id}";
        if ($newUserMessage) {
            $redirectUrl .= "&new_user_message=" . urlencode($newUserMessage);
        }
        header("Location: {$redirectUrl}");
        exit;

    } else {
        echo "Booking error: " . $stmt->error;
    }
}
?>
