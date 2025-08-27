<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

// Initialize a message variable for user feedback
$message = '';

// Check if a registration request was submitted
if (isset($_POST["register"])) {
    $fullname = $_POST["fullname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = $_POST["password"];

    // Connect to the database
    $conn = mysqli_connect("localhost", "root", "", "dentofarm");
    if (!$conn) {
        $message = "Database connection failed.";
    } else {
        // Check if the email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "This email is already registered.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Generate a random verification code
            $verification_code = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
            $encrypted_password = password_hash($password, PASSWORD_DEFAULT);

            // Instantiate and configure PHPMailer
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'testkoto1230@gmail.com';
                $mail->Password = 'ygoe hzoy wcba gvxy';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('testkoto1230@gmail.com', 'Dentofarm Resort');
                $mail->addAddress($email, $fullname);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Email verification';
                $mail->Body    = "Hello, " . htmlspecialchars($fullname) . "!<br><br>This is your verification code: <b style='font-size: 30px;'>" . $verification_code . "</b>";

                $mail->send();

                // Use prepared statement for inserting into the database
                $sql = "INSERT INTO users(fullname, email, phone, password, verification_code, email_verified_at) VALUES (?, ?, ?, ?, ?, NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $fullname, $email, $phone, $encrypted_password, $verification_code);
                $stmt->execute();
                $stmt->close();

                // Redirect to a verification page to prompt the user for the code
                header("Location: verify.php?email=" . urlencode($email));
                exit();

            } catch (Exception $e) {
                $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1" />
 <title>Register</title>
 <link rel="stylesheet" href="register.css" />
 <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet" />
 <style>
   /* Styles from your provided HTML */
    .error-message {
      color: red;
      font-size: 0.9em;
      margin-top: 4px;
    }
    .password__strength {
      margin-top: 6px;
    }
    .strength__bar {
      height: 6px;
      width: 100%;
      background: #eee;
      border-radius: 3px;
      overflow: hidden;
      position: relative;
    }
    .fill-bar {
      height: 100%;
      width: 0%;
      border-radius: 3px;
      transition: width 0.3s ease, background-color 0.3s ease;
      background-color: transparent;
    }
    .password__wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .toggle-password {
      cursor: pointer;
      margin-left: 8px;
      user-select: none;
      color: #555;
    }
    .password-requirements {
      margin-top: 10px;
      font-size: 0.9em;
      list-style: none;
      padding-left: 0;
      color: #555;
    }
    .password-requirements li {
      margin-bottom: 4px;
      display: flex;
      align-items: center;
    }
    .password-requirements li.valid {
      color: green;
    }
    .password-requirements li.invalid {
      color: red;
    }
    .password-requirements li::before {
      content: "✗";
      display: inline-block;
      width: 18px;
      margin-right: 6px;
      color: inherit;
      font-weight: bold;
    }
    .password-requirements li.valid::before {
      content: "✓";
    }
 </style>
</head>
<body>
 <section class="section__container login__container">
   <div class="login__content">
     <h2 class="section__header">Create Account</h2>
     <?php if (!empty($message)): ?>
       <div class="error-message" style="text-align: center; margin-bottom: 10px;"><?php echo htmlspecialchars($message); ?></div>
     <?php endif; ?>
     <form class="login__form" id="registerForm" action="" method="POST" novalidate>
       <div class="form__group">
         <label for="fullname">Full Name</label>
         <input
           type="text"
           id="fullname"
           name="fullname"
           placeholder="Enter your full name"
           required
         />
       </div>
       <div class="form__group">
         <label for="email">Email</label>
         <input
           type="email"
           id="email"
           name="email"
           placeholder="Enter your email"
           required
         />
       </div>
       <div class="form__group">
         <label for="phone">Phone Number</label>
         <input
           type="tel"
           id="phone"
           name="phone"
           placeholder="+63 912 345 6789"
           pattern="^\+?\d{10,15}$"
           required
         />
       </div>
       <div class="form__group">
         <label for="password">Password</label>
         <div class="password__wrapper">
           <input
             type="password"
             id="password"
             name="password"
             placeholder="Create a password"
             maxlength="20"
             required
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,20}$"
             title="Password must be 8-20 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character."
           />
           <span class="toggle-password" id="togglePassword" title="Show password">👁️</span>
         </div>

         <ul class="password-requirements" id="passwordRequirements">
           <li id="req-length" class="invalid">8-20 characters</li>
           <li id="req-uppercase" class="invalid">At least one uppercase letter (A-Z)</li>
           <li id="req-lowercase" class="invalid">At least one lowercase letter (a-z)</li>
           <li id="req-number" class="invalid">At least one number (0-9)</li>
           <li id="req-special" class="invalid">At least one special character (!@#$%^&*)</li>
         </ul>

         <div class="password__strength">
           <div class="strength__bar" id="strengthBar">
             <div class="fill-bar"></div>
           </div>
           <p id="strengthText"></p>
         </div>
         <p class="error-message" id="passwordError"></p>
       </div>

       <div class="form__group">
         <label for="confirm_password">Confirm Password</label>
         <div class="password__wrapper">
           <input
             type="password"
             id="confirm_password"
             name="confirm_password"
             placeholder="Re-enter your password"
             required
           />
           <span class="toggle-password" id="toggleConfirmPassword" title="Show password">👁️</span>
         </div>
         <p class="error-message" id="confirmPasswordError"></p>
       </div>

       <button type="submit" name="register" class="btn">Register</button>
     </form>

     <div class="create__account">
       <p>Already have an account? <a href="login.html">Login here</a></p>
     </div>
   </div>
 </section>

 <script>
  // All your existing JavaScript code goes here...
  const passwordField = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm_password");
    const togglePassword = document.getElementById("togglePassword");
    const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
    const strengthBar = document.getElementById("strengthBar");
    const fillBar = strengthBar.querySelector(".fill-bar");
    const strengthText = document.getElementById("strengthText");
    const passwordError = document.getElementById("passwordError");
    const confirmPasswordError = document.getElementById("confirmPasswordError");

    const reqLength = document.getElementById("req-length");
    const reqUppercase = document.getElementById("req-uppercase");
    const reqLowercase = document.getElementById("req-lowercase");
    const reqNumber = document.getElementById("req-number");
    const reqSpecial = document.getElementById("req-special");

    togglePassword.addEventListener("click", () => {
      const type = passwordField.type === "password" ? "text" : "password";
      passwordField.type = type;
      togglePassword.textContent = type === "text" ? "🙈" : "👁️";
      togglePassword.title = type === "text" ? "Hide password" : "Show password";
    });

    toggleConfirmPassword.addEventListener("click", () => {
      const type = confirmPassword.type === "password" ? "text" : "password";
      confirmPassword.type = type;
      toggleConfirmPassword.textContent = type === "text" ? "🙈" : "👁️";
      toggleConfirmPassword.title = type === "text" ? "Hide password" : "Show password";
    });

    function updateRequirement(element, condition) {
      if (condition) {
        element.classList.remove("invalid");
        element.classList.add("valid");
      } else {
        element.classList.remove("valid");
        element.classList.add("invalid");
      }
    }

    passwordField.addEventListener("input", () => {
      const val = passwordField.value;
      updateRequirement(reqLength, val.length >= 8 && val.length <= 20);
      updateRequirement(reqUppercase, /[A-Z]/.test(val));
      updateRequirement(reqLowercase, /[a-z]/.test(val));
      updateRequirement(reqNumber, /\d/.test(val));
      updateRequirement(reqSpecial, /[\W_]/.test(val));

      let strength = 0;
      if (val.length >= 8) strength++;
      if (/[A-Z]/.test(val)) strength++;
      if (/[a-z]/.test(val)) strength++;
      if (/\d/.test(val)) strength++;
      if (/[\W_]/.test(val)) strength++;

      const strengthPercent = (strength / 5) * 100;
      fillBar.style.width = strengthPercent + "%";

      if (strength <= 2) {
        fillBar.style.backgroundColor = "red";
        strengthText.textContent = "Weak";
      } else if (strength === 3 || strength === 4) {
        fillBar.style.backgroundColor = "orange";
        strengthText.textContent = "Medium";
      } else {
        fillBar.style.backgroundColor = "green";
        strengthText.textContent = "Strong";
      }
    });

    confirmPassword.addEventListener("input", () => {
      if (confirmPassword.value !== passwordField.value) {
        confirmPasswordError.textContent = "Passwords do not match.";
      } else {
        confirmPasswordError.textContent = "";
      }
    });
 </script>
</body>
</html>