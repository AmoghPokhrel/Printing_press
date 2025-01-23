<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fefefe;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      text-align: center;
      width: 100%;
      max-width: 400px;
    }

    .form-container {
      background-color: #f9f3f3;
      padding: 30px 20px;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    h1 {
      color: #E63250;
      font-size: 24px;
      margin-bottom: 20px;
    }

    label {
      display: block;
      text-align: left;
      margin-bottom: 5px;
      font-size: 14px;
      color: #555;
    }

    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      background-color: #fff;
    }

    button {
      width: 100%;
      padding: 10px;
      border: none;
      border-radius: 5px;
      background-color: #E63250;
      color: white;
      font-size: 16px;
      cursor: pointer;
    }

    button:hover {
      background-color: #E63250;
    }

    .forgot-password {
      display: block;
      margin-top: 10px;
      color: #E63250;
      text-decoration: none;
      font-size: 14px;
    }

    .forgot-password:hover {
      text-decoration: underline;
    }

    .signup {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      border: 1px solid #E63250;
      border-radius: 20px;
      color: #E63250;
      text-decoration: none;
      font-size: 16px;
    }

    .signup:hover {
      background-color: #E63250;
      color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="form-container">
      <h1>LOGIN</h1>
      <form>
        <label for="username">Username</label>
        <input type="text" id="username" placeholder="Username" required>
        <label for="password">Password</label>
        <input type="password" id="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <a href="#" class="forgot-password">Forgotten Password?</a>
      </form>
    </div>
    <a href="#" class="signup">Sign Up</a>
  </div>
</body>
</html>
