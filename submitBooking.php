<?php
// submitBooking.php
session_start();
include 'db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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

        if (empty($name) || empty($email) || empty($phone) || empty($checkIn) || empty($checkOut) || $roomId <= 0) {
            throw new Exception("Please fill all required fields and ensure valid selections.");
        }

        // --- USER AUTH ---
        $conn->begin_transaction();
        $userId = null; $tempPassword = '';
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=?");
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($u = $res->fetch_assoc()) {
                $userId = $u['id'];
                $_SESSION['user_id'] = $userId;
            } else {
                $tempPassword = $phone;
                $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)");
                $stmt2->bind_param("ssss", $name, $email, $phone, $hashed);
                $stmt2->execute();
                $userId = $conn->insert_id;
                $_SESSION['user_id'] = $userId;
            }
            $stmt->close();
        }
        $conn->commit();

        // Nights
        $numNights = max(1,(new DateTime($checkIn))->diff(new DateTime($checkOut))->days);
        if ($numNights <= 0) throw new Exception("Invalid check-in/check-out dates.");

        // Room details
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $roomDetails = $stmt->get_result()->fetch_assoc();
        if (!$roomDetails) throw new Exception("Room not found.");
        $stmt->close();

        // Guest distribution
        $children_5_12_count=0; $children_below_5_count=0;
        foreach ($childAges as $a) {
            if ($a == 1) $children_5_12_count++; else $children_below_5_count++;
        }
        $totalExtraBedsNeeded = max(0, $guests - ($roomDetails['base_adults']*$noOfRooms));
        $totalExtraBedsNeeded = min($totalExtraBedsNeeded, $roomDetails['max_extra_with_bed']*$noOfRooms);

        // --- Price mapping ---
        switch ($mealPlanKey) {
            case 'breakfast':
                $baseCol = 'price_with_breakfast';
                $extraCol = 'price_with_extra_bed_bf';
                $childCol = 'price_child_5_12_bf';
                break;
            case 'breakfast_lunch':
                $baseCol = 'price_with_breakfast_lunch';
                $extraCol = 'price_with_extra_bed_bf_lunch';
                $childCol = 'price_child_5_12_bf_lunch';
                break;
            case 'all_meals':
                $baseCol = 'price_with_all_meals';
                $extraCol = 'price_with_extra_bed_all_meals';
                $childCol = 'price_child_5_12_all_meals';
                break;
            default:
                $baseCol = 'standard_price';
                $extraCol = 'price_with_extra_bed_standard';
                $childCol = 'price_child_5_12_standard';
        }

        $basePricePerNight = $roomDetails[$baseCol];
        $extraBedPrice = $roomDetails[$extraCol];
        $childPrice5_12 = $roomDetails[$childCol];
        $childPriceBelow5 = $roomDetails['price_child_below_5'];

        // Cost calc
        $roomCost = $basePricePerNight * $noOfRooms * $numNights;
        $extraBedCost = $totalExtraBedsNeeded * $extraBedPrice * $numNights;
        $child5_12_cost = $children_5_12_count * $childPrice5_12 * $numNights;
        $childBelow5_cost = $children_below_5_count * $childPriceBelow5 * $numNights;
        $totalAmount = $roomCost + $extraBedCost + $child5_12_cost + $childBelow5_cost;

        $childAgesJson = json_encode($childAges);

        // --- Insert Booking ---
        $conn->begin_transaction();
        $sql = "INSERT INTO bookings 
            (room_id,user_id,name,email,phone,check_in,check_out,no_of_rooms,guests,children,extra_beds,meal_plan,total_price,child_ages_json,room_name,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssiiiisdss", $roomId,$userId,$name,$email,$phone,$checkIn,$checkOut,$noOfRooms,$guests,$children,$totalExtraBedsNeeded,$mealPlanKey,$totalAmount,$childAgesJson,$roomDetails['room_name']);
        if(!$stmt->execute()) throw new Exception("Booking insert failed: ".$stmt->error);
        $bookingId = $conn->insert_id;
        $stmt->close();

        // --- Insert booking_rooms ---
        $sql2 = "INSERT INTO booking_rooms 
            (booking_id, room_id, user_id, meal_plan, room_price, extra_bed_price, child_5_12_price, child_below_5_price, total_price) 
            VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("iiisddddd", $bookingId,$roomId,$userId,$mealPlanKey,$roomCost,$extraBedCost,$child5_12_cost,$childBelow5_cost,$totalAmount);
        if(!$stmt2->execute()) throw new Exception("Booking_rooms insert failed: ".$stmt2->error);
        $stmt2->close();

        $conn->commit();

        $response['success']=true;
        $response['message']='Booking successful! Redirecting...';
        $response['redirect_url']='viewBooking.php?id='.$bookingId;

    } catch(Exception $e) {
        if ($conn) $conn->rollback();
        $response['message']='Booking failed: '.$e->getMessage();
    }
} else {
    $response['message']='Invalid request method.';
}
echo json_encode($response);
?>
