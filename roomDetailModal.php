<!-- Room Detail Modal -->
<div class="modal fade" id="roomDetailModal" tabindex="-1" aria-labelledby="roomDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="roomDetailModalLabel">Room Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-0">

        <!-- Image Carousel -->
        <div id="roomModalCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner" id="carouselInner">
            <!-- JS will insert images here -->
          </div>

          <!-- Carousel controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#roomModalCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#roomModalCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>

          <!-- Indicators -->
          <div class="carousel-indicators" id="carouselIndicators">
            <!-- JS will insert dots -->
          </div>
        </div>

        <!-- Room Details -->
        <div class="p-4">
          <h4 id="modalRoomName"></h4>
          <p><strong>Price:</strong> ₹<span id="modalRoomPrice"></span> / Night</p>
          <p><strong>Capacity:</strong> <span id="modalRoomCapacity"></span></p>
          <p><strong>Available Rooms:</strong> <span id="modalRoomAvailable"></span></p>

          <hr>
          <h6>Room Description</h6>
          <p id="modalRoomDesc" class="text-muted"></p>

          <h6 class="mt-4">Amenities</h6>
          <div id="modalAmenities" class="d-flex flex-wrap gap-2">
            <!-- JS will insert badges -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
