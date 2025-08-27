function openBookingModal() {
    document.getElementById("bookingModal").style.display = "flex";
  }
  
  document.getElementById("closeModal").onclick = function () {
    document.getElementById("bookingModal").style.display = "none";
  };
  
  window.onclick = function (event) {
    if (event.target == document.getElementById("bookingModal")) {
      document.getElementById("bookingModal").style.display = "none";
    }
  };
  
  document.getElementById("reserveForm").addEventListener("submit", function (e) {
    e.preventDefault();
    alert("Reservation submitted successfully!");
    document.getElementById("bookingModal").style.display = "none";
  });
  