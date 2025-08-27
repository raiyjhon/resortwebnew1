// show-password.js
document.addEventListener("DOMContentLoaded", () => {
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
  
    togglePassword.addEventListener("click", () => {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      togglePassword.classList.toggle("ri-eye-line");
      togglePassword.classList.toggle("ri-eye-off-line");
    });
  });
  