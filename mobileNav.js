function toggleMobileMenu() {
  var menu = document.getElementById("mobileMenu");
  if (menu.style.display === "block") {
    menu.style.display = "none";
  } else {
    menu.style.display = "block";
  }
}

// Add event listener when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // You can add any initialization code here if needed
  console.log("Mobile navigation initialized");
});
