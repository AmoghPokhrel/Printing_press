<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    <link rel="stylesheet" href="assets/css/index.css"> <!-- Link to external CSS -->
    <script>
        let currentIndex = 0;
        let images = ["image/hall a.jpeg", "image/hall b.jpeg", "image/hall bb.jpeg", "image/hall c.jpeg"];
        let totalImages = images.length;
        let autoSlide;

        function showImage(index) {
            const imageElements = document.querySelectorAll('#imageContainer img');
            imageElements.forEach((img, i) => {
                img.classList.toggle('active', i === index);
            });
        }

        function toggleImage(direction) {
            clearInterval(autoSlide);
            currentIndex = (currentIndex + direction + totalImages) % totalImages;
            showImage(currentIndex);
            autoSlide = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalImages;
                showImage(currentIndex);
            }, 5000);
        }

        document.addEventListener("DOMContentLoaded", () => {
            autoSlide = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalImages;
                showImage(currentIndex);
            }, 5000);
        });
    </script>
</head>

<body>
    <nav>
        <ul>
            <li class="login"><a href="index.php">Home</a></li>
            <li class="login"><a href="pages/templets.php">Templates</a></li>
            <li class="login"><a href="pages/contact.php">Contact</a></li>
            <li class="login"><a href="pages/login.php">Login</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="image-section">
            <div class="image-container" id="imageContainer">
                <img class="active" src="assets/images/img1.jpg" alt="Image 1">
                <img src="assets/images/img2.jpg" alt=" Image 2">
                <img src="assets/images/img3.jpg" alt=" Image 3">
                <img src="assets/images/img4.jpg" alt=" Image 4">
            </div>
            <button class="toggle-btn prev" onclick="toggleImage(-1)">&#10094;</button>
            <button class="toggle-btn next" onclick="toggleImage(1)">&#10095;</button>
        </div>
        <div class="right">
            <h2>Welcome to Our Printing Press Management System</h2>
            <p>Our Printing Press Management System streamlines the entire printing process, from design selection to
                order completion. Customers can easily browse designs, place orders, and track progress, while the
                system ensures efficient task management and seamless communication. Experience a faster, smarter, and
                more organized way to handle printing operations.</p>
        </div>
    </div>
</body>

</html>