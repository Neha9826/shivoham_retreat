<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_rooms  = $_POST['selected_rooms'] ?? []; // array of room IDs
    $check_in        = $_POST['check_in'];
    $check_out       = $_POST['check_out'];
    $no_of_adults    = intval($_POST['guests']);
    $no_of_children  = intval($_POST['children']);
    $meal_plan_ids   = $_POST['meal_plan_id'] ?? [];
    $extra_beds      = $_POST['extra_beds'] ?? []; // array of extra bed IDs (from extra_bed_rates)
    $total_price     = floatval(str_replace('₹', '', $_POST['total_price']));
    $name            = trim($_POST['name'] ?? '');
    $email           = $_POST['email'];
    $phone           = $_POST['phone'];
    $status          = "pending";

    // Count how many times each room is selected
    $roomCountMap = array_count_values($selected_rooms);
    $main_room_id = intval($selected_rooms[0]); // first one for summary
    $total_room_count = array_sum($roomCountMap);

    // ✅ Validate availability per room
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

    // ✅ Insert into bookings table (summary record)
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

        // ✅ booking_rooms (NEW)
        $roomStmt = $conn->prepare("INSERT INTO booking_rooms (booking_id, room_id) VALUES (?, ?)");
        foreach ($selected_rooms as $r_id) {
            $r_id = intval($r_id);
            $roomStmt->bind_param("ii", $booking_id, $r_id);
            $roomStmt->execute();
        }
        $roomStmt->close();

        // ✅ booking_extra_beds (NEW)
        if (!empty($extra_beds)) {
            $bedStmt = $conn->prepare("INSERT INTO booking_extra_beds (booking_id, extra_bed_id) VALUES (?, ?)");
            foreach ($extra_beds as $bed_id) {
                $bid = intval($bed_id);
                $bedStmt->bind_param("ii", $booking_id, $bid);
                $bedStmt->execute();
            }
            $bedStmt->close();
        }

        // ✅ booking_meal_plans (existing)
        if (!empty($meal_plan_ids)) {
            $mealStmt = $conn->prepare("INSERT INTO booking_meal_plans (booking_id, meal_plan_id) VALUES (?, ?)");
            foreach ($meal_plan_ids as $mp_id) {
                $mp_id_int = intval($mp_id);
                $mealStmt->bind_param("ii", $booking_id, $mp_id_int);
                $mealStmt->execute();
            }
            $mealStmt->close();
        }

        // (Optional) Room availability cache table
        if ($status === 'booked') {
            $start = new DateTime($check_in);
            $end   = new DateTime($check_out);

            while ($start < $end) {
                $dateStr = $start->format('Y-m-d');

                foreach ($roomCountMap as $room_id => $roomCount) {
                    $availabilitySql = "
                        INSERT INTO room_availability (room_id, date, booked_rooms)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE booked_rooms = booked_rooms + ?";
                    $availabilityStmt = $conn->prepare($availabilitySql);
                    $availabilityStmt->bind_param("isii", $room_id, $dateStr, $roomCount, $roomCount);
                    $availabilityStmt->execute();
                    $availabilityStmt->close();
                }

                $start->modify('+1 day');
            }
        }

        // ✅ Redirect
        header("Location: viewBooking.php?booking_id=$booking_id");
        exit;

    } else {
        echo "Booking error: " . $stmt->error;
    }
}
?>
