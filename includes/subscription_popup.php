<?php
function show_subscription_popup($message)
{
    ?>
    <div class="subscription-popup-overlay" id="subscriptionPopup">
        <div class="subscription-popup">
            <div class="subscription-popup-content">
                <div class="subscription-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h2>Premium Feature</h2>
                <p><?php echo $message; ?></p>
                <div class="subscription-benefits">
                    <div class="benefit-item">
                        <i class="fas fa-infinity"></i>
                        <span>Unlimited Custom Designs</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Unlimited Template Modifications</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-clock"></i>
                        <span>Priority Processing</span>
                    </div>
                </div>
                <a href="subscription.php" class="subscribe-now-btn">Subscribe Now</a>
                <button class="close-popup" onclick="closeSubscriptionPopup()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <style>
        .subscription-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        .subscription-popup {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.4s ease-out;
        }

        .subscription-popup-content {
            text-align: center;
        }

        .subscription-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .subscription-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .subscription-popup h2 {
            color: #2c3e50;
            margin: 0 0 1rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .subscription-popup p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .subscription-benefits {
            display: grid;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .benefit-item:hover {
            transform: translateX(5px);
            background: #f0f2f5;
        }

        .benefit-item i {
            color: #2ecc71;
            font-size: 1.2rem;
        }

        .benefit-item span {
            color: #2c3e50;
            font-weight: 500;
        }

        .subscribe-now-btn {
            display: inline-block;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 1rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .subscribe-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .close-popup {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s;
        }

        .close-popup:hover {
            color: #2c3e50;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Add responsive styles */
        @media (max-width: 768px) {
            .subscription-popup {
                width: 95%;
                padding: 1.5rem;
            }

            .subscription-icon {
                width: 60px;
                height: 60px;
            }

            .subscription-icon i {
                font-size: 2rem;
            }

            .subscription-popup h2 {
                font-size: 1.5rem;
            }

            .subscription-popup p {
                font-size: 1rem;
            }
        }
    </style>

    <script>
        function closeSubscriptionPopup() {
            const popup = document.getElementById('subscriptionPopup');
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.remove();
            }, 300);
        }
    </script>
    <?php
}
?>