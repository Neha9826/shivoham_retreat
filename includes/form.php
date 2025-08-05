<style>
    .popup_box {
        padding: 30px 20px;
    }
    .popup_inner input,
    .popup_inner select {
        margin-bottom: 15px;
        height: 50px;
        font-size: 16px;
        border-radius: 6px;
    }
    .boxed-btn3 {
        background: #1EC0FF;
        color: white;
        border: none;
        height: 50px;
        font-size: 18px;
        border-radius: 6px;
        transition: background 0.3s ease;
    }
    .boxed-btn3:hover {
        background: #008fc9;

        .select-light {
    color: #999; /* Light gray like placeholder */
}

.select-light option {
    color: #000; /* Normal black once selected */
}

/* Optional: prevent placeholder option from being selected */
.select-light option[disabled] {
    display: none;
}

.popup_inner textarea {
    margin-bottom: 15px;
    font-size: 16px;
    border-radius: 6px;
    padding: 10px;
}


    }
</style>


<!-- ✅ Only one form! -->
<form id="test-form" class="white-popup-block mfp-hide" action="checkAvailability.php" method="POST">
    <div class="popup_box">
        <div class="popup_inner">
            <h3>Check Availability</h3>
            <div class="row">
                <!-- Check-in Date -->
                <div class="col-xl-6">
                    <input type="date" name="check_in" placeholder="Check in date" class="form-control" required>
                </div>

                <!-- Check-out Date -->
                <div class="col-xl-6">
                    <input type="date" name="check_out" placeholder="Check out date" class="form-control" required>
                </div>
                <!-- Adults -->
                <div class="col-xl-6">
                    <input 
                        type="number" 
                        name="adults" 
                        class="form-control" 
                        placeholder="Adults" 
                        min="1" 
                        max="20" 
                        required>
                </div>

                <!-- Children -->
                <div class="col-xl-6">
                    <input 
                        type="number" 
                        name="children" 
                        class="form-control" 
                        placeholder="Children" 
                        min="0" 
                        max="20">
                </div>
                <!-- Room Type (Populated Dynamically) -->
                <div class="col-xl-12">
                    <select name="room_id" class="form-control select-light" required>
                        <option value="" disabled selected hidden>Room Type</option>
                        <?php
                        include 'db.php';
                        $result = mysqli_query($conn, "SELECT id, room_name FROM rooms");
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['room_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Name -->
                
                <!-- Submit -->
                <div class="col-xl-12 mt-2">
                    <button type="submit" class="boxed-btn3">Check Availability</button>
                </div>
            </div>
        </div>
        <!-- Where result will appear -->
        <!-- <div id="form-message"></div> -->

    </div>
</form>
<script>
$(document).ready(function () {
    $('#booking-form').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'backend/checkAvailability.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                $('#form-message').html(response);
                $('#booking-form')[0].reset();
            },
            error: function () {
                $('#form-message').html('<div class="alert alert-danger mt-3">Error submitting the form. Please try again.</div>');
            }
        });
    });
});
</script>

