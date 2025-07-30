<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id        = $_POST['room_id'];
    $check_in       = $_POST['check_in'];
    $check_out      = $_POST['check_out'];
    $no_of_rooms    = $_POST['no_of_rooms'];
    $no_of_adults   = $_POST['guests'];
    $no_of_children = $_POST['children'];
    $meal_plan_ids  = $_POST['meal_plan_id'] ?? []; // array
    $extra_beds     = isset($_POST['extra_beds']) ? count($_POST['extra_beds']) : 0;
    $age_group_id   = isset($_POST['extra_beds']) ? implode(",", $_POST['extra_beds']) : null;
    $total_price = floatval(str_replace('₹', '', $_POST['total_price']));
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email          = $_POST['email'];
    $phone          = $_POST['phone'];

    // Check room availability
    $conflictSql = "SELECT COUNT(*) AS conflict FROM bookings 
                    WHERE room_id = ? AND (check_in < ? AND check_out > ?)";
    $stmt = $conn->prepare($conflictSql);
    $stmt->bind_param("iss", $room_id, $check_out, $check_in);
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc()['conflict'];
    $stmt->close();

    if ($conflict > 0) {
        $_SESSION['booking_error'] = "Sorry, the room is no longer available.";
        header("Location: booking.php?room_id=$room_id&check_in=$check_in&check_out=$check_out");
        exit;
    }

    // Insert into bookings table (NO meal_plan_id here!)
    $status = "pending";
    $stmt = $conn->prepare("INSERT INTO bookings 
        (room_id, check_in, check_out, no_of_rooms, guests, children, extra_beds, extra_bed_age_group_id, total_price, name, email, phone, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
   
    $stmt->bind_param("issiiiisdssss", 
    $room_id, $check_in, $check_out, 
    $no_of_rooms, $no_of_adults, $no_of_children,
    $extra_beds, $age_group_id, $total_price,
    $name, $email, $phone, $status
);


    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        $stmt->close();

        // ✅ Insert selected meal plans into booking_meal_plans table
        $mealStmt = $conn->prepare("INSERT INTO booking_meal_plans (booking_id, meal_plan_id) VALUES (?, ?)");
        foreach ($meal_plan_ids as $mp_id) {
            $mp_id = intval($mp_id);
            $mealStmt->bind_param("ii", $booking_id, $mp_id);
            $mealStmt->execute();
        }
        $mealStmt->close();

        // ✅ Insert/Update room_availability
        if ($status === 'booked') {
            $start = new DateTime($check_in);
            $end = new DateTime($check_out);

            while ($start < $end) {
                $dateStr = $start->format('Y-m-d');

                // Insert or update booked room count
                $availabilitySql = "
                    INSERT INTO room_availability (room_id, date, booked_rooms)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE booked_rooms = booked_rooms + ?";
                $availabilityStmt = $conn->prepare($availabilitySql);
                $availabilityStmt->bind_param("isii", $room_id, $dateStr, $no_of_rooms, $no_of_rooms);
                $availabilityStmt->execute();
                $availabilityStmt->close();

                $start->modify('+1 day');
            }
        }

        // Redirect
        header("Location: viewBooking.php?booking_id=$booking_id");
        
        exit;
    } else {
        echo "Booking error: " . $stmt->error;
    }
}
?>
