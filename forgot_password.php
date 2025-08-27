<!-- forgot_password.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="style.css"> <!-- Optional: your existing CSS -->
</head>
<style>

    /* style.css */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background: #f0f4f8;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

.section__container {
  background: #ffffff;
  padding: 2rem 3rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.section__header {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
  font-size: 1.5rem;
}

.login__form .form__group {
  margin-bottom: 1rem;
}

.login__form label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: bold;
  color: #555;
}

.login__form input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.login__form input:focus {
  border-color: #007bff;
  outline: none;
}

.btn {
  display: block;
  width: 100%;
  padding: 0.75rem;
  background-color: #007bff;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn:hover {
  background-color: #0056b3;
}

.create__account {
  margin-top: 1rem;
  text-align: center;
}

.create__account a {
  color: #007bff;
  text-decoration: none;
}

.create__account a:hover {
  text-decoration: underline;
}

</style>
<body>
  <section class="section__container">
    <div class="login__content">
      <h2 class="section__header">Forgot Password</h2>
      <form class="login__form" action="send_reset_link.php" method="POST">
        <div class="form__group">
          <label for="email">Enter your registered email</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required />
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
      </form>
      <div class="create__account">
        <p><a href="login.html">Back to Login</a></p>
      </div>
    </div>
  </section>
</body>
</html>
