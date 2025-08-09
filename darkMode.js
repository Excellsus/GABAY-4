document.addEventListener("DOMContentLoaded", () => {
  const darkModeSwitch = document.getElementById("darkModeSwitch");
  const logoutBtn = document.getElementById("logoutBtn");

  // Check if the user has a dark mode preference saved
  const savedDarkMode = localStorage.getItem("darkMode");
  if (savedDarkMode === "true") {
    document.body.classList.add("dark-mode"); // Apply dark mode if preference is saved
    darkModeSwitch.checked = true;
  }

  // Toggle dark mode on switch change
  darkModeSwitch.addEventListener("change", () => {
    if (darkModeSwitch.checked) {
      document.body.classList.add("dark-mode");
      localStorage.setItem("darkMode", "true"); // Save dark mode preference
    } else {
      document.body.classList.remove("dark-mode");
      localStorage.setItem("darkMode", "false"); // Save light mode preference
    }
  });
});
