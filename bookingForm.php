<?php
// bookingForm.php
if (!isset($bookingData) || !$bookingData['roomDetails']) {
    echo '<div class="alert alert-danger text-center">Booking data not available.</div>';
    return;
}
$roomDetails = $bookingData['roomDetails'];
?>

<div class="card p-4 shadow-sm mb-4">
    <form id="bookingForm" method="POST" action="submitBooking.php">
        <input type="hidden" name="room_id" value="<?= $roomDetails['id'] ?>">
        <input type="hidden" name="meal_plan" value="<?= htmlspecialchars($bookingData['mealPlan']) ?>">

        <h5 class="mb-3">Booking Details</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Check-in</label>
                <input type="date" name="check_in" id="check_in" class="form-control" value="<?= htmlspecialchars($bookingData['checkIn']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Check-out</label>
                <input type="date" name="check_out" id="check_out" class="form-control" value="<?= htmlspecialchars($bookingData['checkOut']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Rooms</label>
                <input type="number" name="no_of_rooms" id="no_of_rooms" class="form-control" min="1" max="10" value="<?= htmlspecialchars($bookingData['noOfRooms']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Adults</label>
                <input type="number" name="guests" id="guests" class="form-control" min="1" max="20" value="<?= htmlspecialchars($bookingData['guests']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Children</label>
                <input type="number" name="children" id="children" class="form-control" min="0" max="10" value="<?= htmlspecialchars($bookingData['children']) ?>" required>
            </div>
        </div>
        
        <div id="extraBedInfo" class="mt-3"></div>

        <div class="row g-3 mt-1" id="dynamicChildFields">
            </div>

        <h5 class="mb-3 mt-5">Contact Information</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="firstName" name="first_name" required>
            </div>
            <div class="col-md-6">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="last_name" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
        </div>
        
        <hr class="my-4">
        <button type="submit" class="btn btn-primary w-100 py-2">Proceed to Payment Options</button>
    </form>
</div>